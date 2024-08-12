/* Polyfills */
import 'core-js/es/array/includes';
import 'core-js/es/object/assign';
import 'core-js/es/object/values';
import 'core-js/es/object/entries';

import {
	GPAdvancedSaveAndContinue,
	createGPAdvancedSaveAndContinue,
} from './gp-advanced-save-and-continue';
import { DraftManagement } from './draft-management';

window.createGPAdvancedSaveAndContinue = createGPAdvancedSaveAndContinue;
window.GPAdvancedSaveAndContinue = GPAdvancedSaveAndContinue;
window.GPAdvancedSaveAndContinueDraftManagement = DraftManagement;
