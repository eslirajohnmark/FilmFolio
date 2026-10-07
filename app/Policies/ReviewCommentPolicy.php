<?php

namespace App\Policies;

use App\Models\ReviewComment;
use App\Models\User;

class ReviewCommentPolicy
{
    /**
     * The commenter, the review's author, or an admin may remove a comment.
     */
    public function delete(User $user, ReviewComment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->id === $comment->review->user_id
            || $user->isAdmin();
    }
}
