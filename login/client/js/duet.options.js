//For config options that are needed immediately (can't wait for load from server)
//todo:Import server side options into this object, rather than maintaining DUET.config.
DUET.options = {
    server:'server/',
    client:'client/',
    urlPrefix:''
};


/******************************************************************************
 *                            MOD_REWRITE FIX
 * If your server does not have mod_rewrite enabled you need to uncomment the
 * line below.
 *
 * If you're not sure how to remove a javascript comment, this simply means
 * that you need to remove the two slashes at the beginning of the line.
 * Please see more about javascript comments at the following url:
 *
 * https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Code_comments
 *****************************************************************************/

DUET.options.urlPrefix = 'index.php?url=';
