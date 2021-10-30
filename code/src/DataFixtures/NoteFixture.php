<?php
/**
 * @author Scott Anderson <94andersonsc@googlemail.com
 * @license MIT
 */

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Note;
use Faker\Provider\Lorem;
use Faker\Factory;

class NoteFixture extends Fixture
{
    /**
     * @var Factory
     */
    private $faker;
    
    public function load(ObjectManager $manager): void
    {
        $this->faker = Factory::create();
        $this->createNotes($manager);
    }
    
    /**
     * @param manager
     * @param note
     */
    private function createNotes($manager)
    {
        for ($i = 0; $i < 10; $i++) {
            $note = new Note();
            $note->setTitle(Lorem::words(6, true));
            $note->setText(Lorem::text(255));
            $manager->persist($note);
        }
        
        $manager->flush();
    }
}
