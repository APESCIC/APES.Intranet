<?php

namespace Modules\Communications\Services;

use App\Core\Communications\Contracts\PublishIntranetUpdate;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Modules\Communications\Models\UpdatePost;

final class CommunicationsPublishIntranetUpdate implements PublishIntranetUpdate
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function publish(User $author, string $title, string $body): int
    {
        $post = UpdatePost::query()->create([
            'author_id' => $author->id,
            'title' => $title,
            'body' => $this->sanitizer->clean($body),
            'audiences' => [],
            'pinned' => false,
            'comments_enabled' => true,
            'email_alert' => false,
            'published_at' => now(),
        ]);

        return (int) $post->id;
    }
}
