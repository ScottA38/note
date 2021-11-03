<?php

namespace Tests\Controller;

use App\Controller\NoteController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use App\Entity\Note;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityNotFoundException;

/**
 * NoteControlle test case.
 */
class NoteControllerTest extends WebTestCase
{
    const ROUTES = [
        'list' => '/notes', 
        'get' => '/notes/{%d}',
        'update' => '/notes/{%d}',
        'delete' => '/notes/{%d}',
        'create' => '/notes/add'
    ];

    /**
     *
     * @var NoteController
     */
    private $noteController;
    
    /**
     * 
     * @var KernelBrowser
     */
    private $httpClient;
    
    /**
     * 
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->httpClient = static::createClient();
        
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->noteController = new NoteController();
    }

    /**
     * @test
     */
    public function canGetNoteById()
    {        
        $this->httpClient->request('GET', '/notes/4');
        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        
        $data = json_decode($this->httpClient->getResponse()->getContent(), true);
        $this->assertEquals(4, $data['entity']['id']);
    }
    
    /**
     * @test
     */
    public function canUpdateNoteById()
    {
        $title = 'A new title';
        $noteRepository = $this->entityManager->getRepository(Note::class);
        
        $this->httpClient->request('PATCH', "/notes/1?title=$title");
        $this->assertEquals(Response::HTTP_OK, $this->httpClient->getResponse()->getStatusCode());
        
        /** @var Note $note **/
        $note = $noteRepository->find(1);
        $this->assertEquals($title, $note->getTitle());
    }
    
    /**
     * @test
     */
    public function canDeleteNoteById()
    {
       $noteRepository = $this->entityManager->getRepository(Note::class);
       
       $this->httpClient->request('DELETE', '/notes/10');
       $this->assertEquals(Response::HTTP_OK, $this->httpClient->getResponse()->getStatusCode());
       
       $note = $noteRepository->find(10);
    }
    
    /**
     * @test
     * @expectedException Doctrine\Orm\EntityNotFoundException
     */
    public function cannotDeleteNonExistentEntity()
    {   
        $this->httpClient->request('DELETE', '/notes/1000');
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->noteController = null;

        parent::tearDown();
    }
}

