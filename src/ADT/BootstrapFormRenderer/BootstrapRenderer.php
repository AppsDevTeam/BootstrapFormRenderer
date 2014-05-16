<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

/**
 * Modified for easy snippet rendering by Apps Dev Team (http://appsdevteam.com)
 *
 * @copyright Copyright (c) 2014 Tomáš Kudělka (tomas@appsdevteam.com)
 * @license BSD
 */

namespace ADT\BootstrapFormRenderer;

use Nette;
use Nette\Forms\Controls;
use Nette\Iterators\Filter;
use Nette\Bridges\FormsLatte\FormMacros;
use Nette\Templating\FileTemplate;
use Nette\Utils\Html;


if (!class_exists('Nette\Bridges\FormsLatte\FormMacros')) {
	class_alias('Nette\Latte\Macros\FormMacros', 'Nette\Bridges\FormsLatte\FormMacros');
}


/**
 * Created with twitter bootstrap in mind.
 *
 * <code>
 * $form->setRenderer(new Kdyby\BootstrapFormRenderer\BootstrapRenderer);
 * </code>
 *
 * @author Pavel Ptacek
 * @author Filip Procházka
 * @author Tomáš Kudělka
 */
class BootstrapRenderer extends \Kdyby\BootstrapFormRenderer implements Nette\Forms\IFormRenderer
{
	public function render(Nette\Forms\Form $form, $mode = NULL, $args = NULL)
	{
		if ($this->template === NULL) {
			if ($presenter = $form->lookup('Nette\Application\UI\Presenter', FALSE)) {
				/** @var \Nette\Application\UI\Presenter $presenter */
				$this->template = clone $presenter->getTemplate();

			} else {
				$this->template = new FileTemplate();
				$this->template->registerFilter(new Nette\Latte\Engine());
			}
		}

		if ($this->form !== $form) {
			$this->form = $form;

			// translators
			if ($translator = $this->form->getTranslator()) {
				$this->template->setTranslator($translator);
			}

			// controls placeholders & classes
			foreach ($this->form->getControls() as $control) {
				$this->prepareControl($control);
			}

			$formEl = $form->getElementPrototype();
			if (!($classes = self::getClasses($formEl)) || stripos($classes, 'form-') === FALSE) {
				$formEl->addClass('form-horizontal');
			}

		} elseif ($mode === 'begin') {
			foreach ($this->form->getControls() as $control) {
				/** @var \Nette\Forms\Controls\BaseControl $control */
				$control->setOption('rendered', FALSE);
			}
		}

		$this->template->setFile(__DIR__ . '/@form.latte');
		$this->template->setParameters(
			array_fill_keys(array('control', '_control', 'presenter', '_presenter'), NULL) +
			array('_form' => $this->form, 'form' => $this->form, 'renderer' => $this)
		);
		$this->template->_control = $this->form->parent;	

		if ($mode === NULL) {
			if ($args) {
				$this->form->getElementPrototype()->addAttributes($args);
			}
			$this->template->render();

		} elseif ($mode === 'begin') {
			FormMacros::renderFormBegin($this->form, (array)$args);

		} elseif ($mode === 'end') {
			FormMacros::renderFormEnd($this->form);

		} else {

			$attrs = array('input' => array(), 'label' => array());
			foreach ((array) $args as $key => $val) {
				if (stripos($key, 'input-') === 0) {
					$attrs['input'][substr($key, 6)] = $val;

				} elseif (stripos($key, 'label-') === 0) {
					$attrs['label'][substr($key, 6)] = $val;
				}
			}

			$this->template->setFile(__DIR__ . '/@parts.latte');
			$this->template->mode = $mode;
			$this->template->attrs = (array) $attrs;
			$this->template->render();
		}
	}
}
