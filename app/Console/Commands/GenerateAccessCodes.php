<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class GenerateAccessCodes extends Command
{
    protected $signature = 'accesscode:generate {plan} {total}';

    protected $description = 'Generate access codes based on subscription plan';

    public function handle()
    {
        $plan = strtolower($this->argument('plan'));
        $total = (int) $this->argument('total');

        $allowedPlans = ['trial', 'monthly', 'semiannual', 'annual'];

        if (!in_array($plan, $allowedPlans)) {
            $this->error('Invalid plan. Allowed plans are: trial, monthly, semiannual, annual.');
            return Command::FAILURE;
        }

        for ($i = 0; $i < $total; $i++) {

            do {
                $code = 'TPH-' . strtoupper(Str::random(6));
            } while (DB::table('access_code_tbl')->where('access_code', $code)->exists());

            DB::table('access_code_tbl')->insert([
                'access_code' => $code,
                'status' => 0,
                'access_date' => null,
                'access_expired' => null,
                'subscription_plan' => $plan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info("{$total} {$plan} access code(s) generated successfully.");

        return Command::SUCCESS;
    }
}