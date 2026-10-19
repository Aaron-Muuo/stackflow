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
 * make:model — Generate a new Model class
 *
 * Usage: php stackflow make:model Student
 *        php stackflow make:model Product --migration
 */
class MakeModelCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:model')
            ->setDescription('Create a new Model class')
            ->addArgument('name', InputArgument::REQUIRED, 'The model class name (e.g. Student)')
            ->addOption('migration', 'm', InputOption::VALUE_NONE, 'Also create a migration for this model')
            ->addOption('api', 'a', InputOption::VALUE_NONE, 'Also create an API controller for this model');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $path = BASE_PATH . '/app/Models/' . $name . '.php';

        if (file_exists($path)) {
            $io->error("Model [{$name}] already exists.");
            return Command::FAILURE;
        }

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $this->getStub($name));

        $io->success("Model created: app/Models/{$name}.php");

        if ($input->getOption('migration')) {
            $table     = $this->toSnakePlural($name);
            $timestamp = date('Y_m_d_His');
            $migName   = "{$timestamp}_create_{$table}_table";
            $migPath   = BASE_PATH . '/app/migrations/' . $migName . '.php';

            @mkdir(dirname($migPath), 0755, true);
            file_put_contents($migPath, $this->getMigrationStub($migName, $table));
            $io->success("Migration created: app/migrations/{$migName}.php");
        }

        if ($input->getOption('api')) {
            $apiPath = BASE_PATH . '/app/Api/' . $name . 'Api.php';
            @mkdir(dirname($apiPath), 0755, true);
            file_put_contents($apiPath, $this->getApiStub($name));
            $io->success("API controller created: app/Api/{$name}Api.php");
        }

        return Command::SUCCESS;
    }

    private function getStub(string $name): string
    {
        $table = $this->toSnakePlural($name);
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Models;

        use StackFlow\Database\Model;

        class {$name} extends Model
        {
            protected static string \$table = '{$table}';

            protected static array \$fillable = [
                // 'name', 'email',
            ];

            protected static array \$hidden = [
                // 'password',
            ];

            protected static array \$rules = [
                // 'name'  => 'required|min:2|max:100',
                // 'email' => 'required|email|unique:{$table}',
            ];

            // -----------------------------------------------------------------
            // Custom Actions
            // -----------------------------------------------------------------

            // public static function register(array \$data): static
            // {
            //     \$instance = new static(\$data);
            //     if (!\$instance->isValid()) {
            //         throw new \\InvalidArgumentException(json_encode(\$instance->errors()));
            //     }
            //     \$instance->save();
            //     return \$instance;
            // }
        }
        PHP;
    }

    private function getMigrationStub(string $migName, string $table): string
    {
        $class = str_replace(' ', '', ucwords(str_replace('_', ' ', $migName)));
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
                    \$table->id();
                    \$table->string('name');
                    \$table->timestamps();
                });
            }

            public function down(): void
            {
                \$this->dropIfExists('{$table}');
            }
        }
        PHP;
    }

    private function getApiStub(string $name): string
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
                \$data   = \$request->validate({$name}::\$rules);
                \${$var} = {$name}::create(\$data);
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
                \${$var} = {$name}::findOrFail(\$request->params['id']);
                \${$var}->delete();
                return \$this->json(['message' => '{$name} deleted.']);
            }
        }
        PHP;
    }

    private function toSnakePlural(string $name): string
    {
        // CamelCase to snake_case then pluralize (simple)
        $snake = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
        return rtrim($snake, 's') . 's'; // naive pluralize
    }
}
