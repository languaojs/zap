<?php

namespace Zap\Core\Utils;

class Flasher
{
    /**
     * Append a flash message to the queue
     */
    public function set(string $type, string $title, string $message): void 
    {
        $_SESSION['flash'][] = [
            'type'    => $type,
            'title'   => $title,
            'message' => $message
        ];
    }

    /**
     * Display all flash messages and clear the session queue
     */
    public function flash(): void 
    {
        if (empty($_SESSION['flash'])) {
            return;
        }

        $icons = [
            'success' => '&#10004;',
            'info'    => '&#8505;',
            'warning' => '&#9888;',
            'error'   => '&#10006;'
        ];

        echo "<div class='flash-container'>";

        foreach ($_SESSION['flash'] as $flash) {
            $type = $flash['type'];
            $icon = $icons[$type] ?? $icons['info'];

            echo "
            <div class='flash-message flash-{$type}'>
                <div class='flash-icon'>{$icon}</div>
                <div class='flash-body'>
                    <div class='flash-title'>" . htmlspecialchars($flash['title'], ENT_QUOTES, 'UTF-8') . "</div>
                    <div class='flash-text'>" . htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') . "</div>
                </div>
                <button class='flash-close' onclick=\"this.parentElement.remove()\">&times;</button>
            </div>
            ";
        }

        echo "</div>";

        unset($_SESSION['flash']);
    }
}