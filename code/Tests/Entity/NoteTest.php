<?php
/**
 * @author Scott Anderson <94andersonsc@googlemail.com
 * @license MIT
 */

namespace tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Note;

class NoteTest extends TestCase
{
    public function canUpdateTitle()
    {
        $note = new Note();
        $title = 'My Note';
        $note->setTitle($title);
        $this->assertSame($title, $note->getTitle());
    }
}

