<?php

declare(strict_types=1);

namespace App\Pages;

use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Widgets\Display\Text;
use StackFlow\Widgets\Input\Button;
use StackFlow\Widgets\Layout\Column;
use StackFlow\Widgets\Layout\Container;
use StackFlow\Widgets\Layout\Row;
use StackFlow\Widgets\Layout\Scaffold;
use StackFlow\Widgets\Widget;

/**
 * StackFlow Minimalist Welcome Page
 *
 * Implements a clean, Apple-inspired minimalist landing interface:
 *  - Pure white background
 *  - Apple SF Pro / iOS system typography
 *  - Clean header: Logo + GitHub & Website links
 *  - Centered hero: Clean headline & narrative with primary CTA
 */
class WelcomePage extends Page
{
    public function title(): string
    {
        return 'Stackflow — The Declarative PHP Framework';
    }

    public function theme(): string
    {
        return 'light';
    }

    public function build(Request $request): Widget
    {
        // 1. Clean Apple-style Header (Logo + GitHub + Website)
        $header = new class extends Widget {
            public function render(): string
            {
                return <<<HTML
                <header class="w-full border-b border-gray-100 bg-white/90 backdrop-blur-md sticky top-0 z-30">
                    <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
                        <!-- Logo -->
                        <a href="/" class="flex items-center gap-2.5 text-gray-950 font-semibold tracking-tight text-base hover:opacity-80 transition-opacity">
                            <span class="w-7 h-7 rounded-sm bg-black text-white flex items-center justify-center font-bold text-xs tracking-tight">
                                SF
                            </span>
                            <span>Stackflow</span>
                        </a>

                        <!-- Right Links: GitHub and Website -->
                        <div class="flex items-center gap-6 text-sm font-medium">
                            <a href="https://stackflow.dev" target="_blank" rel="noopener noreferrer" class="text-gray-500 hover:text-gray-950 transition-colors">
                                Website
                            </a>
                            <a href="https://github.com/Aaron-Muuo/stackflow" target="_blank" rel="noopener noreferrer" class="text-gray-500 hover:text-gray-950 transition-colors flex items-center gap-1.5">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                                <span>GitHub</span>
                            </a>
                        </div>
                    </div>
                </header>
                HTML;
            }
        };

        // 2. Center Content (Title and Narrative)
        $hero = Container::make([
            'maxWidth' => 'xl',
            'class' => 'my-auto py-20 sm:py-32 flex flex-col items-center text-center',
            'child' => Column::make([
                'align' => 'center',
                'gap' => 'gap-6',
                'children' => [
                    // Title
                    Text::make([
                        'tag' => 'h1',
                        'value' => 'The declarative PHP framework.',
                        'class' => 'text-4xl sm:text-6xl md:text-7xl font-semibold tracking-tight text-gray-950 max-w-3xl leading-[1.08]',
                    ]),

                    // Narrative
                    Text::make([
                        'tag' => 'p',
                        'value' => 'Stackflow brings the simplicity and philosophy of modern mobile frameworks like Android and Flutter to PHP. Compose your web applications using clean, reusable widget trees with zero compilation overhead and native speed.',
                        'class' => 'text-lg sm:text-xl text-gray-500 font-normal max-w-2xl leading-relaxed tracking-normal mt-2',
                    ]),

                    // Minimalist Action Buttons
                    Row::make([
                        'gap' => 'gap-4',
                        'align' => 'center',
                        'class' => 'mt-4 pt-2',
                        'children' => [
                            Button::make([
                                'label' => 'Get Started',
                                'href' => 'https://github.com/Aaron-Muuo/stackflow#readme',
                                'variant' => 'primary',
                                'class' => '!bg-black hover:!bg-gray-800 !text-white rounded-sm px-6 py-2.5 text-sm font-medium tracking-tight shadow-none transition-all',
                            ]),
                            Button::make([
                                'label' => 'Documentation →',
                                'href' => 'https://github.com/Aaron-Muuo/stackflow#readme',
                                'variant' => 'ghost',
                                'class' => 'text-sm font-medium text-gray-600 hover:text-black rounded-sm px-5 py-2.5 hover:bg-gray-100 transition-all',
                            ]),
                        ],
                    ]),
                ],
            ]),
        ]);

        // 3. Subtle Clean Footer
        $footer = new class extends Widget {
            public function render(): string
            {
                return <<<HTML
                <footer class="w-full py-8 border-t border-gray-100 text-center text-xs text-gray-400 font-normal">
                    <div class="max-w-5xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <span>Stackflow PHP &bull; Declarative UI, Native Speed</span>
                        <span>PHP 8.2+</span>
                    </div>
                </footer>
                HTML;
            }
        };

        return Scaffold::make([
            'class' => 'bg-white text-gray-900 min-h-screen flex flex-col justify-between font-sans',
            'appBar' => $header,
            'body' => $hero,
            'bottomNav' => $footer,
        ]);
    }
}
