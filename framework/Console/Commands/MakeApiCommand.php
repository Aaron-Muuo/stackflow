<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * make:api — Generate a REST API controller
 *
 * Usage: php stackflow make:api StudentApi
 */
class MakeApiCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:api')
            ->setDescription('Create a new REST API controller')
            ->addArgument('name', InputArgument::REQUIRED, 'The API controller name (e.g. StudentApi)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));

        if (!str_ends_with($name, 'Api') && !str_ends_with($name, 'Controller')) {
            $name .= 'Api';
        }

        $dir = BASE_PATH . '/app/Api';
        $path = $dir . '/' . $name . '.php';

        if (file_exists($path)) {
            $io->error("API controller [{$name}] already exists at app/Api/{$name}.php");
            return Command::FAILURE;
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stub = <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Api;

        use StackFlow\Api\ApiController;
        use StackFlow\Core\Request;
        use StackFlow\Core\Response;

        class {$name} extends ApiController
        {
            public function index(Request \$request): Response
            {
                return \$this->success([
                    ['id' => 1, 'name' => 'Sample item']
                ]);
            }

            public function show(Request \$request): Response
            {
                \$id = \$request->params['id'] ?? null;
                return \$this->success([
                    'id' => \$id,
                    'name' => 'Sample item ' . \$id
                ]);
            }

            public function store(Request \$request): Response
            {
                \$data = \$this->validate(\$request, [
                    'name' => 'required|min:2',
                ]);

                return \$this->success(\$data, 'Resource created successfully.', 201);
            }

            public function update(Request \$request): Response
            {
                \$id = \$request->params['id'] ?? null;
                return \$this->success(\$request->body, 'Resource updated successfully.');
            }

            public function destroy(Request \$request): Response
            {
                return \$this->success([], 'Resource deleted successfully.');
            }
        }
        PHP;

        file_put_contents($path, $stub);

        $io->success("API controller created: app/Api/{$name}.php");

        return Command::SUCCESS;
    }
}
