<?php

declare(strict_types=1);

namespace App\Pages;

use App\Layouts\BlankLayout;
use StackFlow\Core\Request;
use StackFlow\Core\Response;
use StackFlow\Core\Session;
use StackFlow\Page\Page;
use StackFlow\Widgets\Container\Card;
use StackFlow\Widgets\Display\Link;
use StackFlow\Widgets\Display\Span;
use StackFlow\Widgets\Display\Text;
use StackFlow\Widgets\Input\Button;
use StackFlow\Widgets\Input\Checkbox;
use StackFlow\Widgets\Input\Form;
use StackFlow\Widgets\Input\Input;
use StackFlow\Widgets\Layout\Column;
use StackFlow\Widgets\Layout\Row;
use StackFlow\Widgets\Widget;

/**
 * LoginPage — Standard StackFlow Authentication View
 *
 * Implements form validation, universal AJAX submissions, and dummy credential
 * checking with StackFlow toast notifications.
 */
class LoginPage extends Page
{
    /**
     * Master Layout: Minimal Centered Canvas.
     */
    protected ?string $layout = BlankLayout::class;

    public function title(): string
    {
        return 'Sign In — Stackflow';
    }

    /**
     * Handle form submission & validation.
     */
    public function action(Request $request): ?Response
    {
        if (!$request->isMethod('POST')) {
            return null;
        }

        $email = trim((string) ($request->input('email') ?? ''));
        $password = (string) ($request->input('password') ?? '');

        // 1. Validation
        $errors = [];
        if ($email === '') {
            $errors['email'][] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors['password'][] = 'Password must be at least 6 characters.';
        }

        if (!empty($errors)) {
            return Response::json([
                'status'  => 'error',
                'errors'  => $errors,
                'message' => 'Please fix the errors below.',
                'toast'   => [
                    'title'    => 'Validation Error',
                    'message'  => reset($errors)[0] ?? 'Please correct the highlighted fields.',
                    'variant'  => 'danger',
                    'position' => 'top-center',
                ],
            ], 422);
        }

        // 2. Dummy Authentication Check before database implementation
        // Always returns "No account found" toast as requested
        if ($request->isAjax()) {
            return Response::json([
                'status' => 'error',
                'toast'  => [
                    'title'       => 'Authentication Failed',
                    'message'     => 'No account found with these credentials.',
                    'variant'     => 'danger',
                    'position'    => 'top-center',
                    'duration'    => 4000,
                    'dismissible' => true,
                ],
                'message' => 'No account found with these credentials.',
            ], 401);
        }

        // Non-AJAX fallback
        Session::flash('error', 'No account found with these credentials.');
        return Response::redirect('/login');
    }

    /**
     * Content rendered inside the BlankLayout shell.
     */
    public function content(Request $request): ?Widget
    {
        return Column::make([
            'gap' => 'gap-6',
            'children' => [
                // Top Brand Header
                Column::make([
                    'align' => 'items-center',
                    'gap'   => 'gap-2',
                    'class' => 'text-center',
                    'children' => [
                        '<a href="/" class="inline-flex items-center justify-center w-10 h-10 rounded-sm bg-indigo-600 text-white font-bold font-mono text-base hover:opacity-90 transition-opacity">&lt;/&gt;</a>',
                        Text::make([
                            'value'  => 'Welcome back',
                            'tag'    => 'h1',
                            'size'   => 'text-2xl sm:text-3xl',
                            'weight' => 'font-bold',
                            'color'  => 'text-gray-900',
                            'class'  => 'tracking-tight mt-2',
                        ]),
                        Text::make([
                            'value' => 'Enter your credentials to access your account.',
                            'color' => 'text-gray-500',
                            'size'  => 'text-sm',
                        ]),
                    ],
                ]),

                // Login Card Form
                Card::make([
                    'shadow'  => 'none',
                    'rounded' => 'sm',
                    'padding' => 'p-6 sm:p-8',
                    'class'   => 'bg-white border border-gray-200/80',
                    'child'   => Form::make([
                        'action' => '/login',
                        'method' => 'POST',
                        'class'  => 'space-y-5',
                        'children' => [
                            Input::make([
                                'name'        => 'email',
                                'type'        => 'email',
                                'label'       => 'Email Address',
                                'placeholder' => 'you@example.com',
                                'required'    => true,
                                'value'       => $request->get('email', ''),
                            ]),

                            Input::make([
                                'name'        => 'password',
                                'type'        => 'password',
                                'label'       => 'Password',
                                'placeholder' => '••••••••',
                                'required'    => true,
                            ]),

                            Row::make([
                                'align'   => 'items-center',
                                'justify' => 'justify-between',
                                'class'   => 'pt-1',
                                'children' => [
                                    Checkbox::make([
                                        'name'    => 'remember',
                                        'label'   => 'Remember for 30 days',
                                        'checked' => true,
                                    ]),
                                    Link::make([
                                        'href'    => '#',
                                        'label'   => 'Forgot password?',
                                        'variant' => 'primary',
                                        'class'   => 'text-xs hover:underline',
                                    ]),
                                ],
                            ]),

                            Button::make([
                                'label'   => 'Sign in',
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'size'    => 'lg',
                                'class'   => 'w-full justify-center py-2.5 font-semibold text-sm',
                            ]),
                        ],
                    ]),
                ]),

                // Card Footer Links
                Row::make([
                    'justify' => 'justify-center',
                    'align'   => 'items-center',
                    'gap'     => 'gap-1.5',
                    'class'   => 'text-sm text-center text-gray-500',
                    'children' => [
                        Span::make("Don't have an account?"),
                        Link::make([
                            'href'    => '#',
                            'label'   => 'Create an account',
                            'variant' => 'primary',
                            'class'   => 'font-medium',
                        ]),
                    ],
                ]),
            ],
        ]);
    }
}