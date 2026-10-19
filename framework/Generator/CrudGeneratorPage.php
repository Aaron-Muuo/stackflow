<?php

declare(strict_types=1);

namespace StackFlow\Generator;

use StackFlow\Core\Request;
use StackFlow\Core\Response;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Layout\Scaffold;
use StackFlow\Widgets\Layout\Column;
use StackFlow\Widgets\Layout\Row;
use StackFlow\Widgets\Layout\Container;
use StackFlow\Widgets\Container\Card;
use StackFlow\Widgets\Display\Text;
use StackFlow\Widgets\Display\Badge;
use StackFlow\Widgets\Input\Button;
use StackFlow\Widgets\Input\Input;
use StackFlow\Widgets\Feedback\Alert;
use StackFlow\Widgets\Navigation\Navbar;

/**
 * StackFlow Visual CRUD & Code Generator Page
 *
 * Provides a clean in-browser interface for scaffolding Models, Migrations,
 * Widget Pages, and REST API controllers.
 *
 * @package StackFlow\Generator
 */
class CrudGeneratorPage extends Page
{
    public function title(): string
    {
        return 'StackFlow CRUD & Code Generator';
    }

    public function build(Request $request): Widget
    {
        $message = null;
        $createdFiles = [];

        // Handle POST form submission to generate code
        if ($request->isMethod('POST')) {
            $modelName = ucfirst(trim((string) $request->input('model_name', '')));
            $fieldsRaw = trim((string) $request->input('fields', ''));
            $genModel = (bool) $request->input('gen_model', false);
            $genMigration = (bool) $request->input('gen_migration', false);
            $genPages = (bool) $request->input('gen_pages', false);
            $genApi = (bool) $request->input('gen_api', false);

            if ($modelName === '') {
                $message = ['variant' => 'danger', 'text' => 'Model name is required!'];
            } else {
                $createdFiles = $this->generate(
                    $modelName,
                    $fieldsRaw,
                    $genModel,
                    $genMigration,
                    $genPages,
                    $genApi
                );
                $message = [
                    'variant' => 'success',
                    'text'    => "Successfully generated " . count($createdFiles) . " file(s) for {$modelName}!"
                ];
            }
        }

        $alertWidget = $message
            ? Alert::make([
                'message' => $message['text'],
                'variant' => $message['variant'],
                'title'   => ($message['variant'] === 'success') ? 'Generator Complete' : 'Error',
            ])
            : Text::make(['value' => '']);

        $resultsWidget = !empty($createdFiles)
            ? Card::make([
                'title' => 'Generated Files & Route Setup',
                'class' => 'mt-6',
                'children' => [
                    Text::make([
                        'value' => 'The following files have been created:',
                        'size'  => 'text-sm',
                        'weight' => 'font-semibold',
                        'class' => 'mb-2 text-emerald-600 dark:text-emerald-400'
                    ]),
                    Text::make([
                        'value' => implode("\n", array_map(fn($f) => "✓ {$f}", $createdFiles)),
                        'tag'   => 'pre',
                        'class' => 'bg-gray-900 text-gray-100 p-4 rounded-xl text-xs font-mono mb-4'
                    ]),
                    Text::make([
                        'value' => 'Add the following routes to your routes/web.php file:',
                        'size'  => 'text-sm',
                        'weight' => 'font-semibold',
                        'class' => 'mb-2 text-indigo-600 dark:text-indigo-400'
                    ]),
                    Text::make([
                        'value' => $this->getRouteCode($request->input('model_name', '')),
                        'tag'   => 'pre',
                        'class' => 'bg-gray-900 text-gray-100 p-4 rounded-xl text-xs font-mono'
                    ]),
                ]
            ])
            : Text::make(['value' => '']);

        return Scaffold::make([
            'appBar' => Navbar::make([
                'brand' => '⚡ StackFlow Generator',
                'links' => [
                    ['label' => 'Home', 'href' => '/'],
                    ['label' => 'CRUD Generator', 'href' => '/stackflow/crud', 'active' => true],
                ],
                'actions' => [
                    Badge::make(['label' => 'Dev Mode', 'variant' => 'warning']),
                ]
            ]),
            'body' => Container::make([
                'maxWidth' => '2xl',
                'child' => Column::make([
                    'class' => 'py-8',
                    'children' => [
                        Text::make([
                            'value' => 'Visual CRUD & Code Generator',
                            'tag'   => 'h1',
                            'size'  => 'text-3xl',
                            'weight' => 'font-black',
                            'class' => 'tracking-tight'
                        ]),
                        Text::make([
                            'value' => 'Generate models, migrations, Android-inspired Flutter-style widget pages, and REST APIs directly from your browser.',
                            'size'  => 'text-gray-500 dark:text-gray-400',
                            'class' => 'mb-6'
                        ]),
                        $alertWidget,
                        Card::make([
                            'title' => 'Generate New Resource',
                            'children' => [
                                Text::make([
                                    'value' => '<form method="POST" action="/stackflow/crud" class="space-y-4">' . csrf_field(),
                                    'raw'   => true
                                ]),
                                Input::make([
                                    'name'        => 'model_name',
                                    'label'       => 'Resource / Model Name',
                                    'placeholder' => 'e.g. Student, Product, Order',
                                    'required'    => true,
                                    'hint'        => 'Singular CamelCase format',
                                ]),
                                Input::make([
                                    'name'        => 'fields',
                                    'label'       => 'Fields (comma-separated)',
                                    'placeholder' => 'name:string, price:decimal, qty:integer, description:text',
                                    'hint'        => 'Format: field:type (supported types: string, text, integer, decimal, boolean)',
                                ]),
                                Text::make([
                                    'value' => '<div class="pt-2"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Components to Generate</label><div class="grid grid-cols-2 gap-3 text-sm">
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="gen_model" value="1" checked class="rounded text-indigo-600"> <span>Model</span></label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="gen_migration" value="1" checked class="rounded text-indigo-600"> <span>Migration</span></label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="gen_pages" value="1" checked class="rounded text-indigo-600"> <span>CRUD Pages (Widget Tree)</span></label>
                                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="gen_api" value="1" checked class="rounded text-indigo-600"> <span>REST API Controller</span></label>
                                    </div></div>',
                                    'raw' => true,
                                ]),
                                Text::make([
                                    'value' => '<div class="pt-4 flex items-center justify-end gap-3">',
                                    'raw'   => true
                                ]),
                                Button::make([
                                    'label'   => '⚡ Generate CRUD',
                                    'type'    => 'submit',
                                    'variant' => 'primary',
                                    'size'    => 'lg',
                                ]),
                                Text::make([
                                    'value' => '</div></form>',
                                    'raw'   => true
                                ]),
                            ]
                        ]),
                        $resultsWidget
                    ]
                ])
            ])
        ]);
    }

    private function generate(
        string $name,
        string $fieldsRaw,
        bool $genModel,
        bool $genMigration,
        bool $genPages,
        bool $genApi
    ): array {
        $created = [];
        $lower = strtolower($name);
        $table = rtrim(strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name))), 's') . 's';

        $fields = [];
        if ($fieldsRaw !== '') {
            foreach (explode(',', $fieldsRaw) as $pair) {
                [$col, $type] = array_pad(explode(':', trim($pair)), 2, 'string');
                $fields[trim($col)] = trim($type);
            }
        }

        // Model
        if ($genModel) {
            $modelPath = BASE_PATH . "/app/Models/{$name}.php";
            @mkdir(dirname($modelPath), 0755, true);
            $fillable = $fields ? "'" . implode("', '", array_keys($fields)) . "'" : "// 'name'";
            $modelCode = "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\Models;\n\nuse StackFlow\Database\Model;\n\nclass {$name} extends Model\n{\n    protected static string \$table = '{$table}';\n    protected static array \$fillable = [{$fillable}];\n}\n";
            file_put_contents($modelPath, $modelCode);
            $created[] = "app/Models/{$name}.php";
        }

        // Migration
        if ($genMigration) {
            $timestamp = date('Y_m_d_His');
            $migName = "{$timestamp}_create_{$table}_table";
            $migPath = BASE_PATH . "/app/migrations/{$migName}.php";
            @mkdir(dirname($migPath), 0755, true);
            $migClass = str_replace(' ', '', ucwords(str_replace('_', ' ', $migName)));
            $colLines = "            \$table->id();\n";
            foreach ($fields as $col => $type) {
                $colLines .= match ($type) {
                    'integer' => "            \$table->integer('{$col}');\n",
                    'decimal' => "            \$table->decimal('{$col}', 10, 2);\n",
                    'text'    => "            \$table->text('{$col}');\n",
                    default   => "            \$table->string('{$col}');\n",
                };
            }
            $colLines .= "            \$table->timestamps();";
            $migCode = "<?php\n\ndeclare(strict_types=1);\n\nuse StackFlow\Database\Migration;\nuse StackFlow\Database\Blueprint;\n\nclass {$migClass} extends Migration\n{\n    public function up(): void\n    {\n        \$this->create('{$table}', function (Blueprint \$table) {\n{$colLines}\n        });\n    }\n\n    public function down(): void\n    {\n        \$this->dropIfExists('{$table}');\n    }\n}\n";
            file_put_contents($migPath, $migCode);
            $created[] = "app/migrations/{$migName}.php";
        }

        // Pages
        if ($genPages) {
            $pagePath = BASE_PATH . "/app/Pages/{$name}/{$name}IndexPage.php";
            @mkdir(dirname($pagePath), 0755, true);
            $pageCode = "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\Pages\\{$name};\n\nuse StackFlow\Core\Request;\nuse StackFlow\Page\Page;\nuse StackFlow\Widgets\Widget;\nuse StackFlow\Widgets\Layout\Scaffold;\nuse StackFlow\Widgets\Layout\Container;\nuse StackFlow\Widgets\Layout\Column;\nuse StackFlow\Widgets\Display\Text;\nuse StackFlow\Widgets\Navigation\Navbar;\nuse StackFlow\Widgets\Input\Button;\nuse StackFlow\Widgets\Data\Table;\nuse App\Models\\{$name};\n\nclass {$name}IndexPage extends Page\n{\n    public function title(): string { return '{$name} List'; }\n    public function build(Request \$request): Widget\n    {\n        \$items = {$name}::all();\n        return Scaffold::make([\n            'appBar' => Navbar::make(['brand' => '{$name} Portal']),\n            'body'   => Container::make([\n                'child' => Column::make([\n                    'children' => [\n                        Text::make(['value' => '{$name} Management', 'tag' => 'h1', 'size' => 'text-2xl', 'weight' => 'font-bold']),\n                        Button::make(['label' => '+ Add {$name}', 'href' => '/{$lower}/create', 'variant' => 'primary']),\n                        Table::make(['columns' => ['id', 'name'], 'rows' => \$items]),\n                    ]\n                ])\n            ])\n        ]);\n    }\n}\n";
            file_put_contents($pagePath, $pageCode);
            $created[] = "app/Pages/{$name}/{$name}IndexPage.php";
        }

        // API
        if ($genApi) {
            $apiPath = BASE_PATH . "/app/Api/{$name}Api.php";
            @mkdir(dirname($apiPath), 0755, true);
            $apiCode = "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\Api;\n\nuse StackFlow\Api\ApiController;\nuse StackFlow\Core\Request;\nuse StackFlow\Core\Response;\nuse App\Models\\{$name};\n\nclass {$name}Api extends ApiController\n{\n    public function index(Request \$request): Response\n    {\n        return \$this->json({$name}::all());\n    }\n}\n";
            file_put_contents($apiPath, $apiCode);
            $created[] = "app/Api/{$name}Api.php";
        }

        return $created;
    }

    private function getRouteCode(string $name): string
    {
        $name = ucfirst(trim($name));
        $lower = strtolower($name);
        return <<<ROUTE
        Router::get('/{$lower}', App\\Pages\\{$name}\\{$name}IndexPage::class);
        Router::get('/{$lower}/create', App\\Pages\\{$name}\\{$name}CreatePage::class);
        Router::get('/api/{$lower}', App\\Api\\{$name}Api::class . '@index');
        ROUTE;
    }
}
