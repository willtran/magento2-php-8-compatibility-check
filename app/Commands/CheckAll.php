<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;


class CheckAll extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'check:all {root-dir} {--php-version=8.1} {--threads=4}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Check all module in an m2 application for PHP8 compatibility';

    protected $excludedModules = [
        'vendor/illuminate',
        'vendor/2tvenom',
        'vendor/aws',
        'vendor/magento'
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $rootDir = $this->input->getArgument('root-dir');
        $magentoRootDisk = Storage::build([
            'driver'    => 'local',
            'root'      => $rootDir
        ]);

        // build module / package info
        $modules = $this->getModulesFromMagentoRoot();
        // spawn processes to check
        $p = new Process(['vendor/bin/phpcs', '--config-set', 'ignore_warnings_on_exit', '1']);
        $p->run();

        $processes = [];
        $numberOfProcess = $this->input->getOption('threads');
        $result = [];
        $errorResults = [];
        $this->output->createProgressBar();
        $this->output->progressStart(count($modules));
        while (count($modules)) {
            if (count($processes) < $numberOfProcess) {
                $module = array_pop($modules);
                // spawn new
                $processes[] = [
                    'module' => $module,
                    'process' => $this->checkModuleForPHP8Compatibility($rootDir, $module)
                ];
                sleep(0.5);
            }
            foreach ($processes as $index => $data) {
                if (!$data['process']->isRunning()) {
                    $finalResult = $data['process']->getExitCodeText();
                    $data['module']['result'] = $finalResult;
                    $result[$data['module']['name']] = $data['module'];
                    if ($finalResult != 'OK') {
                        $data['module']['output'] = $data['process']->getOutput();
                        $errorResults[$data['module']['name']] = $data['module'];
                    }
                    unset($processes[$index]);
                    $this->output->progressAdvance(1);
                }
            }
        }
        $this->output->progressFinish();
        sleep(0.5);
        $this->renderResult($result);
        // show actual error
        if (count($errorResults)) {
            $choice = 0;
            while ($choice !== 'exit') {
                $choice = $this->choice('Show error for module:', array_merge(['exit'], array_column($errorResults, 'name')));
                if ($choice !== 'exit') {
                    echo $errorResults[$choice]['output'];
                }
            }
        }

    }


    /**
     * @param $result
     * @return void
     */
    private function renderResult($result) {
        usort($result, function($a,$b) {
            if ($b['result'] !== 'OK' && $a['result'] == 'OK') return -1; else return 1;
        });
        $headers = ['Module Name', 'Module Path', 'Compatibility'];
        $this->getOutput()->table($headers, $result);
    }

    /**
     * @param $rootDir
     * @param $module
     * @return Process
     */
    private function checkModuleForPHP8Compatibility($rootDir, $module) {
        $process = new Process(['vendor/bin/phpcs', '-p', $rootDir . DIRECTORY_SEPARATOR . $module['path'], '--standard=vendor/phpcompatibility/php-compatibility/PHPCompatibility', '--extensions=php,phtml', '--runtime-set', 'testVersion',  $this->input->getOption('php-version')]);
        $process->setTimeout(0);
        $process->start();
        return $process;
    }

    /**
     * Get modules information from app/code and vendor
     * Return ['module_name'] => [name, path]
     * A m2 module is one with registration.php
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function getModulesFromMagentoRoot() {
        $rootDir = $this->input->getArgument('root-dir');
        $magentoRootDisk = Storage::build([
            'driver'    => 'local',
            'root'      => $rootDir
        ]);
        $modules = [];
        $moduleLocations = ['app' . DIRECTORY_SEPARATOR . 'code', 'vendor'];
        foreach ($moduleLocations as $location) {
            $packageDirs = $magentoRootDisk->directories($location);
            foreach ($packageDirs as $packageDir) {
                if (in_array($packageDir, $this->excludedModules)) {
                    continue;
                }
                foreach ($magentoRootDisk->directories($packageDir) as $moduleDir) {
                    $registrationFilePath = $moduleDir . DIRECTORY_SEPARATOR . 'registration.php';
                    if (!$magentoRootDisk->exists($registrationFilePath)) {
                        continue;
                    }
                    $registrationContent = $magentoRootDisk->get($registrationFilePath);
                    preg_match('/["\']\w*_\w*["\']/', $registrationContent, $matches);
                    $moduleName = count($matches) ? $matches[0] : $moduleDir;
                    $modules[$moduleName] = [
                        'name' => $moduleName,
                        'path' =>   $moduleDir
                    ];
                }
            }
        }
        return $modules;
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
