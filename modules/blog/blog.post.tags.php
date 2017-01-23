<?php

class BlogPostTags extends bff\db\Tags
{
    protected function initSettings()
    {
        $this->tblTags = TABLE_BLOG_TAGS;
        $this->tblTagsIn = TABLE_BLOG_POSTS_TAGS;
        $this->tblTagsIn_ItemID = 'post_id';
        $this->urlItemsListing = $this->adminLink('posts&tag=', 'blog');
    }
}