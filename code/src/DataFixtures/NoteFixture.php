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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class NoteFixture extends Fixture implements ContainerAwareInterface
{   
    use ContainerAwareTrait;
    
    const STATIC_FIXTURES = [
        [
            'title' => 'John bo diddley',
            'text' => 'An epic voyage'
        ],
        [
            'title' => 'Some wild way',
            'text' => 'Adventures abound'
        ],
        [
            'title' => 'The efficaceous Priest',
            'text' => 'He does it his way'
        ],
        [
            'title' => 'The florentine architect',
            'text' => 'molto bene'
        ],
        [
            'title' => 'A porcelain Frieze',
            'text' => 'Curatative chaos and jealousy'
        ]
    ];
    
    /**
     * 
     * @var ObjectManager
     */
    private $manager;
    
    /**
     * @var Factory
     */
    private $faker;
    
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->faker = Factory::create();
        
        $this->createNotes($manager);
    }
    
    public function isStatic()
    {
        return $this->container->getParameter('app.staticfixtures');
    }
    
    /**
     * @param manager
     * @param note
     */
    private function createNotes(ObjectManager $manager)
    {
        if ($this->isStatic()) {
            foreach (static::STATIC_FIXTURES as $fixture) {
                $note = new Note();
                $note->setTitle($fixture['title']);
                $note->setText($fixture['text']);
                $this->manager->persist($note);
            }
        } else {
            for ($i = 0; $i < 10; $i++) {
                $note = new Note();
                $note->setTitle(Lorem::words(6, true));
                $note->setText(Lorem::text(255));
                $this->manager->persist($note);            
            }
        }
        
        $this->manager->flush();
    }
}
