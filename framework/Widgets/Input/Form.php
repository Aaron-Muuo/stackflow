<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Core\Session;
use StackFlow\Widgets\Widget;

/**
 * Form Widget
 *
 * Renders an HTML <form> element with built-in CSRF token protection,
 * HTTP method spoofing (PUT, PATCH, DELETE), and responsive Tailwind styling.
 *
 * Props:
 *  - action: string (form action URL, default: '')
 *  - method: 'GET'|'POST'|'PUT'|'PATCH'|'DELETE' (default: 'POST')
 *  - csrf: bool (automatically inject CSRF token hidden input, default: true)
 *  - enctype: ?string ('multipart/form-data' or 'application/x-www-form-urlencoded')
 *  - child: Widget|string|null
 *  - children: array of Widget|string
 *  - class: ?string
 *  - id: ?string
 *  - onSubmit: ?string (JavaScript onsubmit handler)
 *  - autocomplete: ?string ('on'|'off')
 *
 * @package StackFlow\Widgets\Input
 */
class Form extends Widget
{
    public function render(): string
    {
        $action = (string) $this->prop('action', '');
        $requestedMethod = strtoupper((string) $this->prop('method', 'POST'));
        $csrf = (bool) $this->prop('csrf', true);
        $ajax = (bool) $this->prop('ajax', true);
        $enctype = $this->prop('enctype');
        $child = $this->prop('child');
        $children = (array) $this->prop('children', []);
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id');
        $onSubmit = $this->prop('onSubmit');
        $autocomplete = $this->prop('autocomplete');

        // Browsers only natively support GET and POST in forms.
        // For PUT, PATCH, DELETE, we use POST and spoof via _method hidden field.
        $actualMethod = $requestedMethod === 'GET' ? 'GET' : 'POST';

        $classes = $this->classes('space-y-4 w-full', $extraClass);

        $attrs = $this->attr('action', $action)
            . $this->attr('method', $actualMethod)
            . $this->attr('id', $id)
            . $this->attr('onsubmit', $onSubmit)
            . $this->attr('autocomplete', $autocomplete)
            . ($ajax ? ' data-sf-ajax="true"' : '');

        if ($enctype !== null) {
            $attrs .= $this->attr('enctype', $enctype);
        }

        // Hidden fields: CSRF token and method spoofing
        $hiddenInputs = '';

        if ($actualMethod === 'POST' && $csrf) {
            $token = Session::token();
            $hiddenInputs .= "<input type=\"hidden\" name=\"_token\" value=\"{$this->e($token)}\">\n";
        }

        if (in_array($requestedMethod, ['PUT', 'PATCH', 'DELETE'], true)) {
            $hiddenInputs .= "<input type=\"hidden\" name=\"_method\" value=\"{$requestedMethod}\">\n";
        }

        // Render inner body
        $body = '';
        if ($child !== null) {
            $body .= $this->renderChild($child);
        }
        if (!empty($children)) {
            $body .= $this->renderChildren($children);
        }

        return <<<HTML
        <form{$attrs} class="{$classes}">
            {$hiddenInputs}{$body}
        </form>
        HTML;
    }
}
