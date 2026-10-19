<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * generate:crud — Full CRUD scaffold (Pages + Model + API + Migration + Routes)
 *
 * Usage:
 *   php stackflow generate:crud Product
 *   php stackflow generate:crud Product --fields="name:string,price:decimal,description:text"
 */
class GenerateCrudCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('generate:crud')
            ->setDescription('Generate a complete CRUD scaffold (Pages, Model, API, Migration)')
            ->addArgument('name', InputArgument::REQUIRED, 'Resource name (e.g. Product, Student)')
            ->addOption(
                'fields',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Comma-separated field definitions: "name:string,price:decimal,qty:integer"',
                ''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $name   = ucfirst($input->getArgument('name'));
        $fields = $this->parseFields($input->getOption('fields'));
        $table  = $this->toSnakePlural($name);
        $lower  = strtolower($name);

        $io->title("Generating CRUD scaffold for [{$name}]");

        $created = [];

        // 1. Model
        $modelPath = BASE_PATH . "/app/Models/{$name}.php";
        if (!file_exists($modelPath)) {
            @mkdir(dirname($modelPath), 0755, true);
            file_put_contents($modelPath, $this->generateModel($name, $table, $fields));
            $created[] = "app/Models/{$name}.php";
        }

        // 2. Migration
        $timestamp = date('Y_m_d_His');
        $migName   = "{$timestamp}_create_{$table}_table";
        $migPath   = BASE_PATH . "/app/migrations/{$migName}.php";
        @mkdir(dirname($migPath), 0755, true);
        file_put_contents($migPath, $this->generateMigration($migName, $table, $fields));
        $created[] = "app/migrations/{$migName}.php";

        // 3. Pages: Index, Show, Create, Edit
        $pages = ['IndexPage', 'ShowPage', 'CreatePage', 'EditPage'];
        foreach ($pages as $page) {
            $pagePath = BASE_PATH . "/app/Pages/{$name}/{$name}{$page}.php";
            @mkdir(dirname($pagePath), 0755, true);
            if (!file_exists($pagePath)) {
                file_put_contents($pagePath, $this->generatePage($name, $page, $table, $lower, $fields));
                $created[] = "app/Pages/{$name}/{$name}{$page}.php";
            }
        }

        // 4. API Controller
        $apiPath = BASE_PATH . "/app/Api/{$name}Api.php";
        @mkdir(dirname($apiPath), 0755, true);
        if (!file_exists($apiPath)) {
            file_put_contents($apiPath, $this->generateApi($name, $lower, $fields));
            $created[] = "app/Api/{$name}Api.php";
        }

        // 5. Show route suggestions
        $io->listing($created);

        $io->success("CRUD scaffold generated! Add these routes to routes/web.php:");
        $io->block([
            "Router::get('/{$lower}',           App\\Pages\\{$name}\\{$name}IndexPage::class);",
            "Router::get('/{$lower}/create',    App\\Pages\\{$name}\\{$name}CreatePage::class);",
            "Router::get('/{$lower}/{id}',      App\\Pages\\{$name}\\{$name}ShowPage::class);",
            "Router::get('/{$lower}/{id}/edit', App\\Pages\\{$name}\\{$name}EditPage::class);",
        ], null, 'comment');

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function parseFields(string $raw): array
    {
        if (empty($raw)) {
            return [];
        }
        $fields = [];
        foreach (explode(',', $raw) as $pair) {
            [$col, $type] = array_pad(explode(':', trim($pair)), 2, 'string');
            $fields[trim($col)] = trim($type);
        }
        return $fields;
    }

    private function toSnakePlural(string $name): string
    {
        $snake = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
        return rtrim($snake, 's') . 's';
    }

    private function generateModel(string $name, string $table, array $fields): string
    {
        $fillable = $fields ? "'" . implode("', '", array_keys($fields)) . "'" : "// 'name', 'email'";
        $rules    = '';
        foreach ($fields as $col => $type) {
            $rule  = 'required';
            if ($type === 'string') $rule .= '|min:1|max:255';
            if ($type === 'integer' || $type === 'decimal') $rule .= '|numeric';
            $rules .= "        '{$col}' => '{$rule}',\n";
        }

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Models;

        use StackFlow\Database\Model;

        class {$name} extends Model
        {
            protected static string \$table = '{$table}';

            protected static array \$fillable = [{$fillable}];

            protected static array \$rules = [
        {$rules}    ];
        }
        PHP;
    }

    private function generateMigration(string $migName, string $table, array $fields): string
    {
        $class      = str_replace(' ', '', ucwords(str_replace('_', ' ', $migName)));
        $fieldLines = "            \$table->id();\n";
        foreach ($fields as $col => $type) {
            $fieldLines .= match ($type) {
                'string'  => "            \$table->string('{$col}');\n",
                'text'    => "            \$table->text('{$col}');\n",
                'integer' => "            \$table->integer('{$col}');\n",
                'decimal' => "            \$table->decimal('{$col}', 10, 2);\n",
                'boolean' => "            \$table->boolean('{$col}')->default(false);\n",
                default   => "            \$table->string('{$col}');\n",
            };
        }
        $fieldLines .= "            \$table->timestamps();";

        return <<<PHP
        <?php

        declare(strict_types=1);

        use StackFlow\Database\Migration;
        use StackFlow\Database\Blueprint;

        class {$class} extends Migration
        {
            public function up(): void
            {
                \$this->create('{$table}', function (Blueprint \$table) {
        {$fieldLines}
                });
            }

            public function down(): void
            {
                \$this->dropIfExists('{$table}');
            }
        }
        PHP;
    }

    private function generatePage(string $name, string $page, string $table, string $lower, array $fields): string
    {
        $class = $name . $page;
        return match ($page) {
            'IndexPage'  => $this->generateIndexPage($class, $name, $lower, $fields),
            'ShowPage'   => $this->generateShowPage($class, $name, $lower, $fields),
            'CreatePage' => $this->generateFormPage($class, $name, $lower, $fields, 'Create'),
            'EditPage'   => $this->generateFormPage($class, $name, $lower, $fields, 'Edit'),
            default      => '',
        };
    }

    private function generateIndexPage(string $class, string $name, string $lower, array $fields): string
    {
        $cols = $fields
            ? "['key' => 'id', 'label' => 'ID'],\n" . implode('', array_map(
                fn($c) => "                ['key' => '{$c}', 'label' => '" . ucfirst($c) . "'],\n",
                array_keys($fields)
            ))
            : "['key' => 'id', 'label' => 'ID'],";

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Pages\\{$name};

        use StackFlow\Core\Request;
        use StackFlow\Page\Page;
        use StackFlow\Widgets\Widget;
        use StackFlow\Widgets\Layout\Scaffold;
        use StackFlow\Widgets\Layout\Column;
        use StackFlow\Widgets\Layout\Container;
        use StackFlow\Widgets\Display\Text;
        use StackFlow\Widgets\Navigation\Navbar;
        use StackFlow\Widgets\Input\Button;
        use StackFlow\Widgets\Data\Table;
        use App\Models\\{$name};

        class {$class} extends Page
        {
            public function title(): string { return '{$name} List'; }

            public function build(Request \$request): Widget
            {
                \$items = {$name}::all();

                return Scaffold::make([
                    'appBar' => Navbar::make(['brand' => '{$name} Manager']),
                    'body'   => Container::make([
                        'child' => Column::make([
                            'children' => [
                                Text::make(['value' => '{$name} List', 'tag' => 'h1', 'size' => 'text-2xl', 'weight' => 'font-bold']),
                                Button::make(['label' => '+ New {$name}', 'href' => '/{$lower}/create', 'variant' => 'primary']),
                                Table::make([
                                    'columns' => [
                                        {$cols}
                                        ['key' => 'actions', 'label' => 'Actions'],
                                    ],
                                    'rows'    => array_map(fn(\$item) => array_merge(
                                        (array)\$item,
                                        ['actions' => '<a href="/{$lower}/' . \$item->id . '" class="text-indigo-600 hover:underline mr-2">View</a><a href="/{$lower}/' . \$item->id . '/edit" class="text-amber-600 hover:underline">Edit</a>']
                                    ), \$items),
                                    'striped' => true,
                                    'hover'   => true,
                                ]),
                            ],
                        ]),
                    ]),
                ]);
            }
        }
        PHP;
    }

    private function generateShowPage(string $class, string $name, string $lower, array $fields): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Pages\\{$name};

        use StackFlow\Core\Request;
        use StackFlow\Page\Page;
        use StackFlow\Widgets\Widget;
        use StackFlow\Widgets\Layout\Scaffold;
        use StackFlow\Widgets\Layout\Column;
        use StackFlow\Widgets\Layout\Container;
        use StackFlow\Widgets\Display\Text;
        use StackFlow\Widgets\Navigation\Navbar;
        use StackFlow\Widgets\Input\Button;
        use StackFlow\Widgets\Container\Card;
        use App\Models\\{$name};

        class {$class} extends Page
        {
            public function title(): string { return '{$name} Details'; }

            public function build(Request \$request): Widget
            {
                \$item = {$name}::findOrFail(\$request->params['id'] ?? 0);

                return Scaffold::make([
                    'appBar' => Navbar::make(['brand' => '{$name} Manager']),
                    'body'   => Container::make([
                        'child' => Card::make([
                            'title'    => '{$name} #' . \$item->id,
                            'children' => [
                                Text::make(['value' => json_encode(\$item->toArray(), JSON_PRETTY_PRINT), 'tag' => 'pre']),
                                Button::make(['label' => 'Edit', 'href' => '/{$lower}/' . \$item->id . '/edit', 'variant' => 'primary']),
                                Button::make(['label' => 'Back to list', 'href' => '/{$lower}', 'variant' => 'ghost']),
                            ],
                        ]),
                    ]),
                ]);
            }
        }
        PHP;
    }

    private function generateFormPage(string $class, string $name, string $lower, array $fields, string $mode): string
    {
        $isEdit   = $mode === 'Edit';
        $title    = $isEdit ? "Edit {$name}" : "Create {$name}";
        $action   = $isEdit ? "'/{$lower}/' . \$item->id" : "'/{$lower}'";
        $method   = $isEdit ? 'PUT' : 'POST';

        $inputs = '';
        foreach ($fields as $col => $type) {
            $label = ucfirst($col);
            $inputs .= "                        Input::make(['name' => '{$col}', 'label' => '{$label}', 'type' => '" . ($type === 'integer' ? 'number' : 'text') . "']),\n";
        }
        if (empty($inputs)) {
            $inputs = "                        // Add Input widgets here\n";
        }

        $itemLoad = $isEdit ? "\$item = {$name}::findOrFail(\$request->params['id'] ?? 0);\n        " : '';
        $formAction = ($method === 'POST') ? "/{$lower}" : "/{$lower}/{\$item->id}";

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Pages\\{$name};

        use StackFlow\Core\Request;
        use StackFlow\Page\Page;
        use StackFlow\Widgets\Widget;
        use StackFlow\Widgets\Layout\Scaffold;
        use StackFlow\Widgets\Layout\Column;
        use StackFlow\Widgets\Layout\Container;
        use StackFlow\Widgets\Display\Text;
        use StackFlow\Widgets\Navigation\Navbar;
        use StackFlow\Widgets\Input\Button;
        use StackFlow\Widgets\Input\Input;
        use StackFlow\Widgets\Container\Card;
        use App\Models\\{$name};

        class {$class} extends Page
        {
            public function title(): string { return '{$title}'; }

            public function build(Request \$request): Widget
            {
                {$itemLoad}return Scaffold::make([
                    'appBar' => Navbar::make(['brand' => '{$name} Manager']),
                    'body'   => Container::make([
                        'maxWidth' => 'lg',
                        'child'    => Card::make([
                            'title'    => '{$title}',
                            'children' => [
                                Text::make(['value' => '<form method="POST" action="{$formAction}"><input type="hidden" name="_method" value="{$method}">' . csrf_field(), 'raw' => true]),
        {$inputs}                    Button::make(['label' => 'Save', 'type' => 'submit', 'variant' => 'primary']),
                                Button::make(['label' => 'Cancel', 'href' => '/{$lower}', 'variant' => 'ghost']),
                                Text::make(['value' => '</form>', 'raw' => true]),
                            ],
                        ]),
                    ]),
                ]);
            }
        }
        PHP;
    }

    private function generateApi(string $name, string $lower, array $fields): string
    {
        $var = lcfirst($name);
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Api;

        use StackFlow\Api\ApiController;
        use StackFlow\Core\Request;
        use StackFlow\Core\Response;
        use App\Models\\{$name};

        class {$name}Api extends ApiController
        {
            public function index(Request \$request): Response
            {
                return \$this->json({$name}::all());
            }

            public function show(Request \$request): Response
            {
                \${$var} = {$name}::findOrFail(\$request->params['id']);
                return \$this->json(\${$var}->toArray());
            }

            public function store(Request \$request): Response
            {
                \${$var} = {$name}::create(\$request->validate({$name}::\$rules));
                return \$this->json(\${$var}->toArray(), 201);
            }

            public function update(Request \$request): Response
            {
                \${$var} = {$name}::findOrFail(\$request->params['id']);
                \${$var}->update(\$request->body);
                return \$this->json(\${$var}->toArray());
            }

            public function destroy(Request \$request): Response
            {
                {$name}::findOrFail(\$request->params['id'])->delete();
                return \$this->json(['message' => '{$name} deleted successfully.']);
            }
        }
        PHP;
    }
}
