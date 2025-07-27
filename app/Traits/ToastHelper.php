<?php 
namespace App\Traits;

trait ToastHelper
{
    public function showToast($type, $message)
    {
        $this->dispatch('notify', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}
?>
