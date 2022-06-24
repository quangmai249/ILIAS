/* Copyright (c) 1998-2020 ILIAS open source, Extended GPL, see docs/LICENSE */

import Action from "./action.js";

/**
 * Editor actions can perform model changes and invoke query/command actions
 */
export default class EditorAction extends Action {

  /**
   * @param {string} component
   * @param {string} type
   * @param {Object} params
   */
  constructor(component, type, params = {}) {
    super(component, type, params);
  }

}