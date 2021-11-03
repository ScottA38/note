<?php
/**
 * @author Scott Anderson <94andersonsc@googlemail.com
 * @license MIT
 */

namespace Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Note;

class NoteTest extends TestCase
{
    /**
     * @test
     */
    public function canUpdateTitle()
    {
        $note = new Note();
        $title = 'My Note';
        $note->setTitle($title);
        $this->assertSame($title, $note->getTitle());
    }
}

