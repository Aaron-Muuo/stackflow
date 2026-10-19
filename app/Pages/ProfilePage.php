<?php

declare(strict_types=1);

namespace App\Pages;

use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Layout\Column;
use StackFlow\Widgets\Display\Text;
use StackFlow\Widgets\Container\Card;

class ProfilePage extends Page
{
    /**
     * Parent layout master shell.
     */
    protected ?string $layout = \App\Layouts\auth::class;

    public function title(): string
    {
        return 'profile';
    }

    /**
     * Content rendered inside the parent layout shell.
     */
    public function content(Request $request): ?Widget
    {
        return Column::make([
            'gap' => 'gap-6',
            'children' => [
                Text::make([
                    'value'  => 'profile',
                    'tag'    => 'h1',
                    'size'   => 'text-2xl sm:text-3xl',
                    'weight' => 'font-bold',
                ]),
                Card::make([
                    'child' => Text::make([
                        'value' => 'This page is extending the parent layout.',
                        'color' => 'text-gray-600 dark:text-gray-400',
                    ]),
                ]),
            ],
        ]);
    }
}