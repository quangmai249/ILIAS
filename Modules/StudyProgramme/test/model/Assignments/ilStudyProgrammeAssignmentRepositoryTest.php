<?php

/**
 * @group needsInstalledILIAS
 */
class ilStudyProgrammeAssignmentRepositoryTest extends \PHPUnit\Framework\TestCase
{
    protected $backupGlobals = false;
    protected static $created = [];
    protected static $prg_1;
    protected static $prg_2;
    protected static $usr_1;
    protected static $usr_2;

    public static function setUpBeforeClass() : void
    {
        global $DIC;
        if (!$DIC) {
            include_once("./Services/PHPUnit/classes/class.ilUnitUtil.php");
            try {
                ilUnitUtil::performInitialisation();
            } catch (Exception $e) {
            }
        }
        self::$prg_1 = ilObjStudyProgramme::createInstance();
        self::$prg_1->putInTree(ROOT_FOLDER_ID);
        self::$prg_2 = ilObjStudyProgramme::createInstance();
        self::$prg_2->putInTree(ROOT_FOLDER_ID);
        self::$usr_1 = new ilObjUser();
        self::$usr_1->create();
        self::$usr_2 = new ilObjUser();
        self::$usr_2->create();
    }


    protected function setUp() : void
    {
        PHPUnit_Framework_Error_Deprecated::$enabled = false;


        global $DIC;
        $this->db = $DIC['ilDB'];
    }

    public function test_init()
    {
        $repo = new ilStudyProgrammeAssignmentDBRepository($this->db);
        $this->assertInstanceOf(ilStudyProgrammeAssignmentRepository::class, $repo);
        return $repo;
    }

    /**
     * @depends test_init
     */
    public function test_create($repo)
    {
        $ass_1 = $repo->createFor(self::$prg_2->getId(), self::$usr_1->getId(), 6);
        self::$created[$ass_1->getID()] = $ass_1;
        $ass_2 = $repo->createFor(self::$prg_2->getId(), self::$usr_1->getId(), 6);
        self::$created[$ass_2->getID()] = $ass_2;
        $ass_3 = $repo->createFor(self::$prg_2->getId(), self::$usr_2->getId(), 6);
        self::$created[$ass_3->getID()] = $ass_3;
        $ass_4 = $repo->createFor(self::$prg_2->getId(), self::$usr_2->getId(), 6);
        self::$created[$ass_4->getId()] = $ass_4;
        $this->assertEquals($ass_1->getRootId(), self::$prg_2->getId());
        $this->assertEquals($ass_1->getUserId(), self::$usr_1->getId());
        $this->assertEquals($ass_1->getLastChangeBy(), 6);
        $this->assertNull($ass_1->getRestartDate());
        $this->assertEquals(
            $ass_1->getRestartedAssignmentId(),
            ilStudyProgrammeAssignment::NO_RESTARTED_ASSIGNMENT
        );
    }

    /**
     * @depends test_create
     */
    public function test_save_and_load()
    {
        $repo = new ilStudyProgrammeAssignmentDBRepository($this->db);
        $ass = $repo->get(current(self::$created)->getId());
        $this->assertEquals($ass->getId(), current(self::$created)->getId());
        $this->assertEquals($ass->getRootId(), self::$prg_2->getId());
        $this->assertEquals($ass->getUserId(), self::$usr_1->getId());
        $this->assertEquals($ass->getLastChangeBy(), 6);
        $ass->setRootId(self::$prg_1->getId());
        $ass->setLastChangeBy(self::$usr_2->getId());
        $ass->setRestartDate(DateTime::createFromFormat('Ymd', '20210102'));
        $ass->setRestartedAssignmentId(123);
        $repo->update($ass);

        $repo = new ilStudyProgrammeAssignmentDBRepository($this->db);
        $ass = $repo->get(current(self::$created)->getId());
        $this->assertEquals($ass->getId(), current(self::$created)->getId());
        $this->assertEquals($ass->getRootId(), self::$prg_1->getId());
        $this->assertEquals($ass->getUserId(), self::$usr_1->getId());
        $this->assertEquals($ass->getLastChangeBy(), self::$usr_2->getId());
        $this->assertEquals($ass->getRestartDate()->format('Ymd'), '20210102');
        $this->assertEquals($ass->getRestartedAssignmentId(), 123);
    }

    /**
     * @depends test_save_and_load
     */
    public function test_read_by_prg_id()
    {
        $repo = new ilStudyProgrammeAssignmentDBRepository($this->db);
        $this->assertCount(0, $repo->getByPrgId(-1));

        $asss = $repo->getByPrgId(self::$prg_1->getId());
        $this->assertCount(1, $asss);
        $ass = array_shift($asss);
        $this->assertEquals($ass->getRootId(), self::$prg_1->getId());
        $this->assertEquals($ass->getUserId(), self::$usr_1->getId());

        $asss = $repo->getByPrgId(self::$prg_2->getId());
        $this->assertCount(3, $asss);
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getUserId();
            }, $asss),
            [self::$usr_1->getId(),self::$usr_2->getId(),self::$usr_2->getId()]
        );
        foreach ($asss as $ass) {
            $this->assertEquals($ass->getRootId(), self::$prg_2->getId());
        }
    }


    /**
     * @depends test_save_and_load
     */
    public function test_read_by_usr_id()
    {
        $repo = new ilStudyProgrammeAssignmentDBRepository($this->db);
        $this->assertCount(0, $repo->getByUsrId(-1));

        $asss = $repo->getByUsrId(self::$usr_1->getId());
        $this->assertCount(2, $asss);
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getRootId();
            }, $asss),
            [self::$prg_1->getId(),self::$prg_2->getId()]
        );

        $asss = $repo->getByUsrId(self::$usr_2->getId());
        $this->assertCount(2, $asss);
        foreach ($asss as $ass) {
            $this->assertEquals($ass->getRootId(), self::$prg_2->getId());
        }
    }

    /**
     * @depends test_save_and_load
     */
    public function test_read_by_usr_and_prg_ids()
    {
        $repo = new ilStudyProgrammeAssignmentDBRepository($this->db);
        $this->assertCount(0, $repo->getByUsrIdAndPrgId(-1, -2));

        $asss = $repo->getByUsrIdAndPrgId(self::$usr_2->getId(), self::$prg_2->getId());
        $this->assertCount(2, $asss);
        foreach ($asss as $ass) {
            $this->assertEquals($ass->getRootId(), self::$prg_2->getId());
            $this->assertEquals($ass->getUserId(), self::$usr_2->getId());
        }
    }

    /**
     * @depends test_init
     * @expectedException ilException
     */
    public function test_create_error_user($repo)
    {
        $repo->createFor(self::$prg_1->getId(), -1, 6);
    }


    /**
     * @depends test_init
     * @expectedException ilException
     */
    public function test_create_error_prg($repo)
    {
        $repo->createFor(-1, 6, 6);
    }


    /**
     * @depends test_save_and_load
     */
    public function test_read_due_to_restart()
    {
        $one_day = new DateInterval('P1D');
        $yesterday = new DateTime();
        $yesterday->sub($one_day);
        $today = new DateTime();
        $tomorrow = new DateTime();
        $tomorrow->add($one_day);
        $repo = new ilStudyProgrammeAssignmentDBRepository($this->db);
        $u_a_repo = ilStudyProgrammeDIC::dic()['ilStudyProgrammeUserAssignmentDB'];
        $created = self::$created;
        $repo->update(array_shift($created)->setRestartDate($yesterday));
        $repo->update(array_shift($created)->setRestartDate($today));
        $repo->update(array_shift($created)->setRestartDate($tomorrow));
        $repo->update(array_shift($created)->setRestartDate(null));
        $created = self::$created;
        $ref = [array_shift($created)->getId(),array_shift($created)->getId()];
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getId();
            }, $repo->getDueToRestart()),
            $ref
        );
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getId();
            }, $u_a_repo->getDueToRestart()),
            $ref
        );


        $created = self::$created;
        $repo->update(array_shift($created)->setRestartDate($yesterday));
        $repo->update(array_shift($created)->setRestartDate($today)->setRestartedAssignmentId(123));
        $repo->update(array_shift($created)->setRestartDate($tomorrow)->setRestartedAssignmentId(223));
        $repo->update(array_shift($created)->setRestartDate(null)->setRestartedAssignmentId(323));
        $created = self::$created;
        $ref = [array_shift($created)->getId()];
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getId();
            }, $repo->getDueToRestart()),
            $ref
        );
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getId();
            }, $u_a_repo->getDueToRestart()),
            $ref
        );

        $created = self::$created;
        $repo->update(array_shift($created)->setRestartDate($yesterday)->setRestartedAssignmentId(23));
        $repo->update(array_shift($created)->setRestartDate($today)->setRestartedAssignmentId(123));
        $repo->update(array_shift($created)->setRestartDate($tomorrow)->setRestartedAssignmentId(223));
        $repo->update(array_shift($created)->setRestartDate(null)->setRestartedAssignmentId(323));
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getId();
            }, $repo->getDueToRestart()),
            []
        );
        $this->assertEquals(
            array_map(function ($ass) {
                return $ass->getId();
            }, $u_a_repo->getDueToRestart()),
            []
        );
    }

    public static function tearDownAfterClass() : void
    {
        global $DIC;
        if (!$DIC) {
            include_once("./Services/PHPUnit/classes/class.ilUnitUtil.php");
            ilUnitUtil::performInitialisation();
        }
        global $DIC;
        $db = $DIC['ilDB'];
        try {
            self::$prg_1->delete();
        } catch (Exception $e) {
        }

        try {
            self::$prg_2->delete();
        } catch (Exception $e) {
        }
        self::$usr_1->delete();
        self::$usr_2->delete();
        if (count(self::$created) > 0) {
            $db->manipulate(
                'DELETE FROM ' . ilStudyProgrammeAssignmentDBRepository::TABLE
                . '	WHERE'
                . '	' . $db->in(
                    ilStudyProgrammeAssignmentDBRepository::FIELD_ID,
                    array_keys(self::$created),
                    false,
                    'integer'
                )
            );
        }
    }
}
