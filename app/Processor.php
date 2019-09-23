<?php

/**
 * Process an incoming string.
 * 
 * @author Макс
 */
interface Processor 
{
    public function change(string $data): string;
}
