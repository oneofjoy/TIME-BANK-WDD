<?php
// Only these project-owned portraits can be selected. Never trust a submitted path.
function avatar_options(): array
{
    $options = [];
    for ($number = 1; $number <= 8; $number++) {
        $id = 'avatar-' . str_pad((string)$number, 2, '0', STR_PAD_LEFT);
        $options[$id] = ['label' => 'Avatar ' . $number, 'path' => 'Assets/Avatars/' . $id . '.svg'];
    }
    return $options;
}

function safe_avatar_path(?string $path): ?string
{
    if ($path === null) return null;
    foreach (avatar_options() as $option) {
        if ($path === $option['path'] && is_file(__DIR__ . '/../' . $path)) return $path;
    }
    return null;
}

function avatar_id(?string $path): string
{
    foreach (avatar_options() as $id => $option) if ($path === $option['path']) return $id;
    return 'initials';
}
