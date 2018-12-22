<?php

return [


    /**
     * Enforce uniqueness of shorts?  Defaults to true.
     * If a generated short already exists, a new short will be generated again until the requirements are met.
     *
     */
    'unique' => true,


    /**
     * The length of a generated ID.  Defaults to "11", which means
     * Set it to any positive integer if you
     * want to make sure your ID's will be that size.
     */
    'length' => 11,


    /**
     * Should we include the trashed items when generating a unique ID?
     * This only applies if the softDelete property is set for the Eloquent model.
     * If set to "false", then a new ID could duplicate one that exists on a trashed model.
     * If set to "true", then uniqueness is enforced across trashed and existing models.
     */
    'includeTrashed' => false,



    /**
     * Whether to update the short value when a model is being
     * re-saved (i.e. already exists).  Defaults to false, which
     * means slugs are not updated.
     *
     * Be careful! If you are using shorts to generate URLs, then
     * updating your short automatically might change your URLs which
     * is probably not a good idea from an SEO point of view.
     * Only set this to true if you understand the possible consequences.
     */
    'onUpdate' => false,

];