# Post Collections

A WordPress plugin that provides an admin UI for assigning a collection of posts to another post, and an API for fetching collections.

A post collection is a manually curated list of posts that gets attached to a single post. The collection can then be displayed by the theme however it sees fit. A post collection is similar in construct to ACF's post relationships, except post collections uses a taxonomy for the relationship so the collection can be queried in both directions (fetch the posts in this post's collection; fetch the post that owns this post's collection).

Support for post collections should be added to a post type via the `post_collection_types` filter.
