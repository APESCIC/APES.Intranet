<?php

namespace App\Core\Legal;

/**
 * Audit event names for legal pages, footer links and acceptance.
 */
final class LegalAuditEvents
{
    public const PAGE_DRAFT_SAVED = 'legal.page.draft_saved';

    public const PAGE_PUBLISHED = 'legal.page.published';

    public const PAGE_RESTORED = 'legal.page.restored';

    public const FOOTER_UPDATED = 'legal.footer.updated';

    public const ACCEPTANCE_EXPORTED = 'legal.acceptance.exported';

    public const TERMS_ACCEPTED = 'legal.terms.accepted';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PAGE_DRAFT_SAVED,
            self::PAGE_PUBLISHED,
            self::PAGE_RESTORED,
            self::FOOTER_UPDATED,
            self::ACCEPTANCE_EXPORTED,
            self::TERMS_ACCEPTED,
        ];
    }
}
