<?php

namespace App\Models\Views;

use App\Application\Projections\LibraryImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $team_id
 * @property string $presentation_name
 * @property string $name
 * @property string $file_name
 * @property string|null $mime_type
 * @property int $size
 * @property string $disk
 * @property Carbon|null $created_at
 */
class PresentationImagesView extends ReadOnlyModel
{
    protected $table = 'presentation_images_view';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The public URL is resolved through the file's own disk rather than baked
     * into the view, so it stays correct whether media lives on the local
     * `public` disk or S3. This mirrors Spatie's DefaultPathGenerator layout
     * ("{media_id}/{file_name}"), which is the generator this app is
     * configured with.
     */
    public function toLibraryImage(): LibraryImage
    {
        return new LibraryImage(
            url: Storage::disk($this->disk)->url($this->id.'/'.$this->file_name),
            name: $this->name,
            presentation_id: $this->presentation_id,
            presentation_name: $this->presentation_name,
            created_at: $this->created_at?->toISOString(),
        );
    }
}
