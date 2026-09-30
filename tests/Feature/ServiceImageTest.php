<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceImageTest extends TestCase
{
    use RefreshDatabase;

    private function row(): array
    {
        return ['code' => 'IMG-01', 'name' => 'Layanan gambar', 'unit' => 'pcs', 'is_active' => 1];
    }

    public function test_image_can_be_uploaded_replaced_and_retained(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/services', $this->row() + ['image' => UploadedFile::fake()->image('contoh.jpg', 400, 300)])->assertRedirect('/admin/services');
        $service = Service::firstOrFail();
        $first = $service->image_path;
        Storage::disk('public')->assertExists($first);
        $this->put('/admin/services/'.$service->id, $this->row())->assertRedirect('/admin/services');
        $this->assertSame($first, $service->fresh()->image_path);
        $this->put('/admin/services/'.$service->id, $this->row() + ['image' => UploadedFile::fake()->image('baru.png', 300, 200)])->assertRedirect('/admin/services');
        $service->refresh();
        Storage::disk('public')->assertExists($service->image_path);
        Storage::disk('public')->assertMissing($first);
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $this->get('/pelanggan/catalog')->assertOk()->assertSee('/storage/'.$service->image_path, false);
    }

    public function test_invalid_oversized_and_excessive_dimension_images_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([UploadedFile::fake()->create('file.pdf', 10, 'application/pdf'), UploadedFile::fake()->image('besar.jpg')->size(2049), UploadedFile::fake()->image('lebar.jpg', 6001, 1), UploadedFile::fake()->createWithContent('palsu.jpg', 'bukan gambar')] as $image) {
            $this->post('/admin/services', $this->row() + ['image' => $image])->assertSessionHasErrors('image');
        }
        $this->assertDatabaseCount('services', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_non_admin_cannot_upload_service_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        $this->post('/admin/services', $this->row() + ['image' => UploadedFile::fake()->image('image.jpg')])->assertForbidden();
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }
}
