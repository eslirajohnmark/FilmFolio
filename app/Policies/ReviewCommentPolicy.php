<?php

namespace App\Policies;

use App\Models\ReviewComment;
use App\Models\User;

class ReviewCommentPolicy
{
    /**
     * The commenter or the review's author may remove a comment.
     * (Admins remove inappropriate ones through admin.comments.destroy.)
     */
    public function delete(User $user, ReviewComment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->id === $comment->review->user_id;
    }
}
