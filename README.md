![Definitions - Internal Linkbuilding](https://really-simple-plugins.com/wp-content/uploads/2021/03/definitions-fuji-1.png)

**This is the Really Simple Plugins GitHub repository.**

## Definitions - Internal Linkbuildings

Developed for ourselves to explain, underline and link to specific terms in our knowledgebase that are not known or understood by everyone.

**How it works:** For every post on your website you can designate a keyword. That keyword will than be auto-replaced in the content by a hoverable link with tooltip, if so desired. 
**Example:** As a use case for Complianz.io; we have a custom post type "Definitions", every definition explains a keyword in more detail. If we use "Cookie Policy" on our website, this word will be underlined as a tooltip and on hover will show a infobox with an internal link to this definition. See an example [here](https://complianz.io/customizing-the-cookie-policy-templates/)

## Customization

### Add to custom post-types:

`function my_add_post_type($post_types){
        $post_types[] = 'your-custom-post-type';
        return $post_types;
    }
add_filter('rspdedf_source_post_types','my_add_post_type');`

### Tooltip CSS:

`.rspdedf-preview-content{}`
`.rspdedf-preview-image{}`
`.rspdedf-preview-text{}`
`.rspdedf-read-more{}`

### Hyperlink CSS:

`.rspdedf-definition {}`

### Developers Guide and Contributions

If you're a developer and want to help out, please feel free to contribute anyway you can. We respond to any pull request or issue on Github. 

**Bug report:** Please start an issue, and if you have a fix a pull request. Please explain your issue clearly, and use comments when adding a pull request. Your contribution will be acknowledges on WordPress.org.

**New Features:** New features can also be assigned to issues.

**Translations:** Looking for your own language to be improved or added? Contact [support](https://really-simple-plugins.com/contact/) if you want to be a contributor.
