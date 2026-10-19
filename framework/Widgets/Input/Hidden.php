<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * Hidden Input Widget
 *
 * Renders an HTML <input type="hidden"> field.
 *
 * Props:
 *  - name: string
 *  - value: mixed
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Input
 */
class Hidden extends Widget
{
    public function render(): string
    {
        $name = (string) $this->prop('name', '');
        $value = $this->prop('value', '');
        $id = $this->prop('id');

        $idAttr = $this->attr('id', $id);

        return "<input type=\"hidden\" name=\"{$this->e($name)}\" value=\"{$this->e((string)$value)}\"{$idAttr}>";
    }
}
