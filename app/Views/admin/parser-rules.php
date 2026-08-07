<h1>Parser Rules</h1>

<form method="post" action="<?= htmlspecialchars(url('/admin/parser-rules')) ?>" class="media-form">
    <input type="hidden" name="id" value="0">

    <label>
        Site
        <select name="site_id">
            <?php foreach ($sites as $site): ?>
                <option value="<?= (int)$site['id'] ?>"><?= htmlspecialchars($site['name'] ?: $site['domain']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Field
        <input type="text" name="field_name" placeholder="title" required>
    </label>

    <label>
        Rule type
        <select name="rule_type">
            <option value="regex">Regex</option>
            <option value="meta">Meta</option>
        </select>
    </label>

    <label>
        Rule value
        <textarea name="rule_value" rows="4" required></textarea>
    </label>

    <label>
        Fallback order
        <input type="number" name="fallback_order" value="0">
    </label>

    <label class="checkbox-inline">
        <input type="checkbox" name="is_active" value="1" checked>
        Active
    </label>

    <button type="submit">Save rule</button>
</form>

<?php if (!empty($rules)): ?>
    <h2>Existing rules</h2>
    <ul>
        <?php foreach ($rules as $rule): ?>
            <li>
                <strong><?= htmlspecialchars($rule['field_name'] ?? '') ?></strong>
                (<?= htmlspecialchars($rule['site_name'] ?? '') ?>)
                <span><?= htmlspecialchars($rule['rule_type'] ?? '') ?></span>
                <form method="post" action="<?= htmlspecialchars(url('/admin/parser-rules/delete')) ?>" style="display:inline; margin-left: 12px;">
                    <input type="hidden" name="id" value="<?= (int)$rule['id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
