<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\AffiliateLink;
use App\Models\Transaction;
use App\Models\Commission;
use App\Models\BankDetail;
use App\Models\Bonus;

class ImportNodeData extends Command
{
    protected $signature = 'import:node {--host=} {--port=3306} {--database=} {--username=} {--password=}';

    protected $description = 'Import data from Node MySQL schema (users, affiliate_links, transactions, commissions, bank_details, bonuses)';

    public function handle(): int
    {
        $conn = DB::connection('import_mysql');
        config([
            'database.connections.import_mysql' => [
                'driver' => 'mysql',
                'host' => $this->option('host') ?? env('IMPORT_DB_HOST', '127.0.0.1'),
                'port' => $this->option('port') ?? env('IMPORT_DB_PORT', '3306'),
                'database' => $this->option('database') ?? env('IMPORT_DB_DATABASE'),
                'username' => $this->option('username') ?? env('IMPORT_DB_USERNAME'),
                'password' => $this->option('password') ?? env('IMPORT_DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
        ]);

        // Users
        $this->info('Importing users...');
        $users = DB::connection('import_mysql')->table('users')->get();
        foreach ($users as $u) {
            User::updateOrCreate(['id' => $u->id], [
                'name' => $u->name,
                'email' => $u->email,
                'password' => $u->password_hash ?? bcrypt('changeme123'),
                'role' => $u->role,
                'referrer_id' => $u->referrer_id,
                'coordinator_id' => $u->coordinator_id ?? null,
                'referral_code' => $u->referral_code,
                'is_active' => (bool)$u->is_active,
                'email_verified' => (bool)$u->email_verified,
                'email_verified_at' => $u->email_verified ? now() : null,
                'created_at' => $u->created_at,
                'updated_at' => $u->updated_at,
            ]);
        }

        // Affiliate links
        $this->info('Importing affiliate_links...');
        $links = DB::connection('import_mysql')->table('affiliate_links')->get();
        foreach ($links as $l) {
            AffiliateLink::updateOrCreate(['id' => $l->id], [
                'affiliate_id' => $l->affiliate_id,
                'link_code' => $l->link_code,
                'clicks' => (int)$l->clicks,
                'conversions' => (int)$l->conversions,
                'is_active' => (bool)$l->is_active,
                'created_at' => $l->created_at,
                'updated_at' => $l->updated_at,
            ]);
        }

        // Transactions
        $this->info('Importing transactions...');
        $txs = DB::connection('import_mysql')->table('transactions')->get();
        foreach ($txs as $t) {
            Transaction::updateOrCreate(['id' => $t->id], [
                'customer_email' => $t->customer_email,
                'amount' => $t->amount,
                'affiliate_link_id' => $t->affiliate_link_id,
                'referrer_id' => $t->referrer_id,
                'status' => $t->status,
                'transaction_type' => $t->transaction_type,
                'created_at' => $t->created_at,
                'updated_at' => $t->updated_at,
            ]);
        }

        // Commissions
        $this->info('Importing commissions...');
        $comms = DB::connection('import_mysql')->table('commissions')->get();
        foreach ($comms as $c) {
            Commission::updateOrCreate(['id' => $c->id], [
                'affiliate_id' => $c->affiliate_id,
                'transaction_id' => $c->transaction_id,
                'level' => $c->level,
                'amount' => $c->amount,
                'rate' => $c->rate,
                'status' => $c->status,
                'paid_at' => $c->paid_at,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
            ]);
        }

        // Bank details
        if (DB::connection('import_mysql')->getSchemaBuilder()->hasTable('bank_details')) {
            $this->info('Importing bank_details...');
            $banks = DB::connection('import_mysql')->table('bank_details')->get();
            foreach ($banks as $b) {
                BankDetail::updateOrCreate(['id' => $b->id], [
                    'user_id' => $b->user_id,
                    'bank_name' => $b->bank_name,
                    'account_name' => $b->account_name,
                    'account_number' => $b->account_number,
                    'iban' => $b->iban,
                    'swift_code' => $b->swift_code,
                    'is_default' => (bool)$b->is_default,
                    'created_at' => $b->created_at,
                    'updated_at' => $b->updated_at,
                ]);
            }
        }

        // Bonuses
        if (DB::connection('import_mysql')->getSchemaBuilder()->hasTable('bonuses')) {
            $this->info('Importing bonuses...');
            $bonuses = DB::connection('import_mysql')->table('bonuses')->get();
            foreach ($bonuses as $b) {
                Bonus::updateOrCreate(['id' => $b->id], [
                    'affiliate_id' => $b->affiliate_id,
                    'type' => $b->type,
                    'description' => $b->description,
                    'amount' => $b->amount,
                    'created_at' => $b->created_at,
                    'updated_at' => $b->updated_at,
                ]);
            }
        }

        $this->info('Import complete.');
        return Command::SUCCESS;
    }
}


