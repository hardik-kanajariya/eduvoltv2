<?php

namespace App\Console\Commands;

use App\Events\Audit\DataChangeEvent;
use App\Events\Audit\SystemEvent;
use App\Events\Audit\UserActionEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class DemoEventSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate the event system functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Demonstrating Event/Listener Architecture...');
        $this->newLine();

        // Demo UserActionEvent
        $this->info('1. Dispatching UserActionEvent...');
        Event::dispatch(new UserActionEvent(
            action: 'login',
            resourceType: 'user',
            resourceId: '123',
            newValues: ['last_login' => now()->toISOString()]
        ));
        $this->line('   ✓ User action event dispatched and logged');

        // Demo SystemEvent
        $this->info('2. Dispatching SystemEvent...');
        Event::dispatch(new SystemEvent(
            eventType: 'demo_test',
            component: 'console',
            level: 'info',
            message: 'Event system demonstration completed successfully',
            context: ['command' => 'demo:events', 'timestamp' => now()->toISOString()]
        ));
        $this->line('   ✓ System event dispatched and logged');

        // Demo DataChangeEvent
        $this->info('3. Dispatching DataChangeEvent...');
        Event::dispatch(new DataChangeEvent(
            operation: 'update',
            table: 'demo_table',
            primaryKey: '456',
            oldData: ['status' => 'inactive'],
            newData: ['status' => 'active'],
            changedFields: ['status']
        ));
        $this->line('   ✓ Data change event dispatched and logged');

        $this->newLine();
        $this->info('Event system demonstration completed!');
        $this->line('Check storage/logs/audit.log for audit trail entries.');

        return Command::SUCCESS;
    }
}
