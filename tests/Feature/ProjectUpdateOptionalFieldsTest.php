<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectUpdateOptionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_can_be_saved_with_only_a_title(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $property = Property::create(['code' => 'STR-TST-001', 'development_name' => 'Test Estate', 'address' => 'Lagos']);

        $this->actingAs($admin)
            ->post(route('admin.project-updates.store', $property), ['title' => 'Site update'])
            ->assertRedirect();

        $update = ProjectUpdate::where('property_id', $property->id)->firstOrFail();
        $this->assertSame('Site update', $update->title);
        $this->assertNull($update->body);
        $this->assertNull($update->progress_percentage);
    }

    public function test_progress_still_rejects_values_over_100_when_given(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $property = Property::create(['code' => 'STR-TST-002', 'development_name' => 'Test Estate', 'address' => 'Lagos']);

        $this->actingAs($admin)
            ->post(route('admin.project-updates.store', $property), ['title' => 'Site update', 'progress_percentage' => 150])
            ->assertSessionHasErrors('progress_percentage');
    }
}
