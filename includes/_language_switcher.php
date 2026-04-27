<?php
$language_switcher_mode = $language_switcher_mode ?? 'floating';
?>
<?php if (count(available_locales()) > 1): ?>
    <?php if ($language_switcher_mode === 'navbar'): ?>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-translate me-1"></i><?= htmlspecialchars(available_locales()[current_locale()], ENT_QUOTES, 'UTF-8') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach (available_locales() as $localeCode => $localeLabel): ?>
                    <li>
                        <a class="dropdown-item<?= current_locale() === $localeCode ? ' active' : '' ?>"
                           href="<?= htmlspecialchars(locale_url($localeCode), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($language_switcher_mode === 'inline'): ?>
        <div class="dropdown language-switcher-inline">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-translate me-1"></i><?= htmlspecialchars(available_locales()[current_locale()], ENT_QUOTES, 'UTF-8') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach (available_locales() as $localeCode => $localeLabel): ?>
                    <li>
                        <a class="dropdown-item<?= current_locale() === $localeCode ? ' active' : '' ?>"
                           href="<?= htmlspecialchars(locale_url($localeCode), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="dropdown position-fixed top-0 end-0 m-3" style="z-index: 100;">
            <button class="btn btn-sm btn-light shadow dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?= htmlspecialchars(t('common.language'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars(available_locales()[current_locale()], ENT_QUOTES, 'UTF-8') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach (available_locales() as $localeCode => $localeLabel): ?>
                    <li>
                        <a class="dropdown-item<?= current_locale() === $localeCode ? ' active' : '' ?>"
                           href="<?= htmlspecialchars(locale_url($localeCode), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endif; ?>
