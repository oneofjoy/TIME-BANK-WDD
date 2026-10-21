<?php
require_once __DIR__ . '/auth.php';
require_method('GET');
$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
if (!is_string($search) || !is_string($category)) {
    respond(422, false, 'Enter a valid search and category.');
}
$search = trim($search);
if (mb_strlen($search) > 120 || strlen($category) > 120 || ($category !== '' && !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $category))) {
    respond(422, false, 'Use a search up to 120 characters and a valid category.');
}
$db = database();
$categoryQuery = $db->prepare('SELECT category_id, name, slug FROM skill_categories ORDER BY category_id');
$categoryQuery->execute();
$categories = $categoryQuery->fetchAll();
if ($category !== '' && !in_array($category, array_column($categories, 'slug'), true)) {
    respond(422, false, 'This category is not available.');
}
$sql = "SELECT o.offering_id, o.title, COALESCE(NULLIF(o.description, ''), s.description, '') AS description,
               o.duration_minutes, o.credit_cost, s.name AS skill_name,
               c.name AS category_name, c.slug AS category_slug, u.full_name AS tutor_name, o.tutor_id, p.avatar_path AS tutor_avatar
        FROM skill_offerings o
        JOIN skills s ON s.skill_id = o.skill_id
        JOIN skill_categories c ON c.category_id = s.category_id
        JOIN users u ON u.user_id = o.tutor_id
        LEFT JOIN user_profiles p ON p.user_id=u.user_id
        WHERE o.is_active = 1 AND o.verification_status <> 'rejected'
              AND u.account_status = 'active' AND u.role = 'member'";
$parameters = [];
if ($category !== '') {
    $sql .= ' AND c.slug = ?';
    $parameters[] = $category;
}
if ($search !== '') {
    // Treat %, _ and the escape character as literal keyword characters.
    $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    $sql .= " AND (o.title LIKE ? ESCAPE '!' OR s.name LIKE ? ESCAPE '!' OR COALESCE(NULLIF(o.description, ''), s.description, '') LIKE ? ESCAPE '!')";
    array_push($parameters, $pattern, $pattern, $pattern);
}
$sql .= ' ORDER BY o.offering_id DESC';
$query = $db->prepare($sql);
$query->execute($parameters);
$offerings = $query->fetchAll();
foreach ($offerings as &$offering) {
    $offering['offering_id'] = (int)$offering['offering_id'];
    $offering['tutor_id'] = (int)$offering['tutor_id'];
    $offering['tutor_avatar'] = safe_avatar_path($offering['tutor_avatar']);
    $offering['duration_minutes'] = (int)$offering['duration_minutes'];
}
unset($offering);
respond(200, true, '', ['offerings' => $offerings, 'categories' => $categories]);
