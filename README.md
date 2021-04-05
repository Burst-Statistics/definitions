![Definitions - Internal Linkbuilding](https://really-simple-plugins.com/wp-content/uploads/2021/03/definitions-fuji-1.png)

**This is the Really Simple Plugins GitHub repository.**

## Definitions - Internal Linkbuilding

Add to custom post-types:

'function my_add_post_type($post_types){
        $post_types[] = 'definition';
        return $post_types;
    }
add_filter('wpdef_post_types','my_add_post_type');'

Coming soon...
