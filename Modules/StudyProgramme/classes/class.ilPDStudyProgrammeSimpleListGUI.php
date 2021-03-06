<?php

/* Copyright (c) 2015 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

require_once("Services/Block/classes/class.ilBlockGUI.php");
require_once('./Modules/StudyProgramme/classes/class.ilObjStudyProgrammeAdmin.php');

/**
 * Personal Desktop-Presentation for the Study Programme
 *
 * @author : Richard Klees <richard.klees@concepts-and-training.de>
 * @author : Stefan Hecken <stefan.hecken@concepts-and-training.de>
 * @ilCtrl_IsCalledBy ilPDStudyProgrammeSimpleListGUI: ilColumnGUI
 */
class ilPDStudyProgrammeSimpleListGUI extends ilBlockGUI
{
    const BLOCK_TYPE = "prgsimplelist";
    
    /**
     * @var ilLanguage
     */
    protected $il_lng;
    
    /**
     * @var ilUser
     */
    protected $il_user;
    
    /**
     * @var ilAccessHandler
     */
    protected $il_access;
    
    /**
     * @var ilSetting
     */
    protected $il_setting;

    /**
     * @var ilStudyProgrammeAssignment[]
     */
    protected $users_assignments;

    /**
    * @var visible_on_pd_mode
    */
    protected $visible_on_pd_mode;
    
    /**
    * @var show_info_message
    */
    protected $show_info_message;

    public function __construct()
    {
        global $DIC;

        parent::__construct();

        $lng = $DIC['lng'];
        $ilUser = $DIC['ilUser'];
        $ilAccess = $DIC['ilAccess'];
        $ilSetting = $DIC['ilSetting'];
        $this->il_lng = $lng;
        $this->il_user = $ilUser;
        $this->il_access = $ilAccess;
        $this->il_setting = $ilSetting;
        $this->il_logger = ilLoggerFactory::getLogger('prg');

        $this->sp_user_assignment_db = ilStudyProgrammeDIC::dic()['ilStudyProgrammeUserAssignmentDB'];

        // No need to load data, as we won't display this.
        if (!$this->shouldShowThisList()) {
            return;
        }

        $this->getUsersAssignments();
        //check which kind of option is selected in settings
        $this->getVisibleOnPDMode();
        //check to display info message if option "read" is selected
        $this->getToShowInfoMessage();
        
        // As this won't be visible we don't have to initialize this.
        if (!$this->userHasReadableStudyProgrammes()) {
            return;
        }

        $this->setTitle($this->il_lng->txt("objs_prg"));
    }
    
    public function getHTML()
    {
        // TODO: This should be determined from somewhere up in the hierarchy, as
        // this will lead to problems, when e.g. a command changes. But i don't see
        // how atm...
        if (!$this->shouldShowThisList()) {
            return "";
        }
        
        if (!$this->userHasReadableStudyProgrammes()) {
            return "";
        }
        return parent::getHTML();
    }
    
    public function getDataSectionContent()
    {
        $content = "";
        foreach ($this->users_assignments as $assignment) {
            if (!$this->isReadable($assignment)) {
                continue;
            }

            try {
                $list_item = $this->new_ilStudyProgrammeAssignmentListGUI($assignment);
                $list_item->setShowInfoMessage($this->show_info_message);
                $list_item->setVisibleOnPDMode($this->visible_on_pd_mode);
                $content .= $list_item->getHTML();
            } catch (ilStudyProgrammeNoProgressForAssignmentException $e) {
                $this->il_logger->alert("$e");
            } catch (ilStudyProgrammeTreeException $e) {
                $this->il_logger->alert("$e");
            }
        }
        return $content;
    }

    /**
     * @inheritdoc
     */
    public function getBlockType() : string
    {
        return self::BLOCK_TYPE;
    }

    /**
     * @inheritdoc
     */
    protected function isRepositoryObject() : bool
    {
        return false;
    }
    
    public function fillDataSection()
    {
        assert($this->userHasReadableStudyProgrammes()); // We should not get here.
        $this->tpl->setVariable("BLOCK_ROW", $this->getDataSectionContent());
    }
    
    
    protected function userHasVisibleStudyProgrammes()
    {
        if (count($this->users_assignments) == 0) {
            return false;
        }
        foreach ($this->users_assignments as $assignment) {
            if ($this->isVisible($assignment)) {
                return true;
            }
        }
        return false;
    }

    protected function userHasReadableStudyProgrammes()
    {
        if (count($this->users_assignments) == 0) {
            return false;
        }
        foreach ($this->users_assignments as $assignment) {
            if ($this->isReadable($assignment)) {
                return true;
            }
        }
        return false;
    }
    
    protected function getVisibleOnPDMode()
    {
        $this->visible_on_pd_mode = $this->il_setting->get(ilObjStudyProgrammeAdmin::SETTING_VISIBLE_ON_PD);
    }

    protected function hasPermission(ilStudyProgrammeAssignment $assignment, $permission)
    {
        $prg = ilObjStudyProgramme::getInstanceByObjId($assignment->getRootId());
        return $this->il_access->checkAccess($permission, "", $prg->getRefId(), "prg", $prg->getId());
    }

    protected function getToShowInfoMessage()
    {
        $viewSettings = new ilPDSelectedItemsBlockViewSettings($GLOBALS['DIC']->user(), (int) $_GET['view']);
        $this->show_info_message = $viewSettings->isStudyProgrammeViewActive();
    }

    protected function isVisible(ilStudyProgrammeAssignment $assignment)
    {
        return $this->hasPermission($assignment, "visible");
    }

    protected function isReadable(ilStudyProgrammeAssignment $assignment)
    {
        if ($this->visible_on_pd_mode == ilObjStudyProgrammeAdmin::SETTING_VISIBLE_ON_PD_ALLWAYS) {
            return true;
        }

        return $this->hasPermission($assignment, "read");
    }
    
    protected function shouldShowThisList()
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        return ($_GET["cmd"] == "jumpToSelectedItems" ||
                ($ctrl->getCmdClass() == "ildashboardgui" && $ctrl->getCmd() == "show")
            ) && !$_GET["expand"];
    }
    
    protected function getUsersAssignments()
    {
        $this->users_assignments = $this->sp_user_assignment_db->getInstancesOfUser($this->il_user->getId());
    }
    
    protected function new_ilStudyProgrammeAssignmentListGUI(ilStudyProgrammeAssignment $a_assignment)
    {
        $prg = ilObjStudyProgramme::getInstanceByObjId($assignment->getRootId());
        $progress = $prg->getProgressForAssignment($a_assignment->getId());
        $progress_gui = new ilStudyProgrammeProgressListGUI($progress);
        $progress_gui->setOnlyRelevant(true);
        return $progress_gui;
    }
}
