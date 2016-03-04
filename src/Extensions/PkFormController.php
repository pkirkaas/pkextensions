<?php
/** An abstract "Partials" controller to managae forms. 
 * Extends the currently empty PkPartialsController class - with the idea that
 * a page could contain multiple forms, the same form can appear on several pages,
 * and each form should have it's own processing method.
 */
namespace PkExtensions;
abstract class PkFormController extends PkPartialController {
}
