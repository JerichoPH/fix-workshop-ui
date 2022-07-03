<?php

namespace App\Console\Commands;

use App\Model\DetectorUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DetectorUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'detector:user {operation} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 生成检测台厂家
     * @param string|null $name
     * @return DetectorUser
     */
    final private function generateDetectorUser(?string $name): DetectorUser
    {
        $access_key = Str::uuid();
        $secret_key = Str::uuid();
        return DetectorUser::with([])->create([
            "name" => $name,
            "access_key" => $access_key,
            "secret_key" => $secret_key,
        ]);
    }

    /**
     * Execute the console command.
     */
    final public function handle(): void
    {
        $detector_user = $this->{$this->argument("operation")}($this->argument("name"));
        $this->info("名称：{$detector_user->name}");
        $this->info("accessKey：{$detector_user->access_key}");
        $this->info("secretKey：{$detector_user->secret_key}");
    }
}
