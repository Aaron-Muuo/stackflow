<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Avatar Widget
 *
 * Renders a user profile avatar with image support, automatic name initials fallback,
 * and status indicator pill.
 *
 * Props:
 *  - src: ?string (image URL)
 *  - name: ?string (user name for initials and alt)
 *  - size: 'xs'|'sm'|'md'|'lg'|'xl' (default: 'md')
 *  - status: 'online'|'offline'|'busy'|null
 *  - rounded: 'full'|'lg' (default: 'full')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Display
 */
class Avatar extends Widget
{
    public function render(): string
    {
        $src = $this->prop('src');
        $name = (string) $this->prop('name', '');
        $size = (string) $this->prop('size', 'md');
        $status = $this->prop('status');
        $rounded = (string) $this->prop('rounded', 'full');
        $extraClass = $this->prop('class', '');

        $sizeMap = [
            'xs' => ['box' => 'w-6 h-6 text-[10px]', 'dot' => 'w-1.5 h-1.5'],
            'sm' => ['box' => 'w-8 h-8 text-xs', 'dot' => 'w-2 h-2'],
            'md' => ['box' => 'w-10 h-10 text-sm', 'dot' => 'w-2.5 h-2.5'],
            'lg' => ['box' => 'w-12 h-12 text-base', 'dot' => 'w-3 h-3'],
            'xl' => ['box' => 'w-16 h-16 text-lg', 'dot' => 'w-3.5 h-3.5'],
        ];

        $boxSize = $sizeMap[$size]['box'] ?? $sizeMap['md']['box'];
        $dotSize = $sizeMap[$size]['dot'] ?? $sizeMap['md']['dot'];

        $roundedClass = $rounded === 'lg' ? 'rounded-lg' : 'rounded-full';

        // Avatar body: Image or Initials
        if ($src !== null && $src !== '') {
            $avatarBody = "<img src=\"{$this->e((string)$src)}\" alt=\"{$this->e($name)}\" class=\"w-full h-full object-cover {$roundedClass}\">";
        } else {
            $initials = $this->getInitials($name);
            $avatarBody = "<div class=\"w-full h-full flex items-center justify-center bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 font-semibold {$roundedClass}\">{$this->e($initials)}</div>";
        }

        // Status indicator dot
        $statusDot = '';
        if ($status !== null) {
            $statusColor = match ($status) {
                'online' => 'bg-emerald-500',
                'busy' => 'bg-rose-500',
                'offline' => 'bg-gray-400',
                default => 'bg-emerald-500',
            };
            $statusDot = "<span class=\"absolute bottom-0 right-0 block {$dotSize} {$statusColor} rounded-full ring-2 ring-white dark:ring-gray-900\"></span>";
        }

        $containerClass = $this->classes('relative inline-block shrink-0', $boxSize, $extraClass);

        return <<<HTML
        <div class="{$containerClass}">
            {$avatarBody}
            {$statusDot}
        </div>
        HTML;
    }

    private function getInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        if (empty($words) || $words[0] === '') {
            return '?';
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 2));
        }

        return strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
    }
}
