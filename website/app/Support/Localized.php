<?php

namespace App\Support;

trait Localized
{
    /** Read a bilingual attribute (name, description, address...) in the current locale. */
    public function tr(string $field): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';
        $value = $this->{$field.'_'.$locale};
        return $value ?: $this->{$field.'_'.($locale === 'en' ? 'ar' : 'en')};
    }
}
