<?php
if (!isset($user) || !$user instanceof WP_User) {
    // We throw an error here because the $user variable is required
    throw new RuntimeException('The $user variable is required.');
}
//checking all other variables
if (!isset($badge_class, $enabled_text, $checked_attribute, $title, $description, $type, $forcible)) {
    return; // Return early if variables are not set
}
?>
<p>
    <label class="radio-label">
        <strong><?php echo $title ?></strong>
        <input type="radio" name="preferred_method" value="<?php echo $type ?>"
               class="radio-input" <?php echo $checked_attribute; ?>/>
    </label>
    <br>
    <?php
    echo $description;
    // Get the user's role.
    $user_roles = $user->roles;
    // If this is in the forced roles, we do not show the "disable" link.
    ?>
</p>
