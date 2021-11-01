<?php
/**
 * @author Scott Anderson <94andersonsc@googlemail.com
 * @license MIT
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Note;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Doctrine\ORM\EntityNotFoundException;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Doctrine\Orm\QueryBuilder;
use DeviceDetector\DeviceDetector;

class NoteController extends AbstractController
{       
    const UPDATEABALE_FIELDS = ['title', 'text'];
    const TWIG_INFO_TEMPLATE = 'note/note_info.html.twig';
    
    protected function renderResponse(
        Request $request, 
        Note $note, 
        array $twigParams = [],
        string $responseCode = Response::HTTP_OK, 
        array $responseArgs = []
    )
    {
        $userAgent = $request->headers->get('User-Agent');
        if (stripos($userAgent, 'postman') !== false) {
            $responseArgs['content-type'] = 'application/json';
            $entityData = [
                'action' => $request->attributes->get('_route'),
                'entity' => $note
            ]; 
            
            return new Response(
                json_encode($entityData),
                $responseCode,
                $responseArgs
            );
        }
        $responseArgs['content-type'] = 'text/html';
        $view = $this->renderView(
            static::TWIG_INFO_TEMPLATE,
            $twigParams
        );
        
        return new Response(
            $view,
            $responseCode,
            $responseArgs
        );
    }
    
    /**
     * @Route("/notes/add", name="create_note", methods={"PUT"})
     * 
     * @return Note
     */
    public function createNote(Request $request): Response
    {
        $title = $request->query->get('title');
        if ($title === null) {
            throw new ParameterNotFoundException('title');
        }
        $text = $request->query->get('text');
        $em  = $this->getDoctrine()->getManager();
        
        $note = new Note();
        $note->setTitle($title);
        $note->setText($text);
        $em->persist($note);
        
        $em->flush();
        
        return $this->renderResponse(
            $request, 
            $note, 
            [
                'action' => sprintf('Created Note with id %s', $note->getId()),
                'note_title' => $note->getTitle(),
                'note_text' => $note->getText()
            ],
            Response::HTTP_CREATED
        );
    }
    
    /**
     * @Route("/notes/{id}", name="get_note", methods={"GET"})
     * 
     * @param int $id
     * 
     * @return Response
     */
    public function getNoteById(Request $request, int $id): Response 
    {
        $note = $this->getDoctrine()
        ->getRepository(Note::class)
        ->find($id);
        
        if ($note === null) {
            throw new EntityNotFoundException(sprintf('No such entity found for id \'%s\'', $id));
        }
        
        return $this->renderResponse(
            $request, 
            $note,
            [
                'action' => 'Info',
                'note_title' => $note->getTitle(),
                'note_text' => $note->getText()
            ]
        );
    }
    
    /**
     * @Route("/notes/{id}", name="update_note", methods={"PATCH"})
     * @param int $id
     * 
     * @return Response
     */
    public function updateNoteById(Request $request, int $id): Response
    {
        $em  = $this->getDoctrine()->getManager();
        /** @var Note $note **/
        $note = $this->getDoctrine()
        ->getRepository(Note::class)
        ->find($id);
        $params = $request->query->all();
        
        if ($note === null) {
            throw new EntityNotFoundException(sprintf('No such entity found for id \'%s\'', $id));
        } else if (!count(array_intersect(static::UPDATEABALE_FIELDS, array_keys($params)))) {
            throw new ParameterNotFoundException(implode(', ', static::UPDATEABALE_FIELDS));    
        }
        
        foreach ($params as $key => $param) {
            if (in_array($key, static::UPDATEABALE_FIELDS)) {
                $method = 'set' . $key;
                $note->$method($param);
            }
        }
        
        $em->persist($note);
        $em->flush();
                
        return $this->renderResponse(
            $request, 
            $note, 
            [
                'action' => 'Updated',
                'note_title' => $note->getTitle(),
                'note_text' => $note->getText()
            ]
        ); 
    }
    
    
   /**
    * @Route("/notes/{id}", name="delete_note", methods={"DELETE"})
    * @param int $id
    * 
    * @return Response
    */
    public function deleteNoteById(Request $request, int $id): Response
    {
        $em  = $this->getDoctrine()->getManager();
        /** @var Note $note **/
        $note = $this->getDoctrine()
        ->getRepository(Note::class)
        ->find($id);
        
        $em->remove($note);
        $em->flush();
        
        return new Response(
            sprintf('Deleted entity with id \'%s\'', $id),
            Response::HTTP_OK
        );
        
        return $this->renderResponse(
            $request,
            $note,
            [
                'action' => sprintf('Deleted entity with id \'%s\'', $id),
                'note_title' => $note->getTitle(),
                'note_text' => $note->getText()
            ]
        ); 
    }
    
    /**
     * @Route("/notes", name="list_notes", methods={"GET", "POST"})
     * 
     * @return Response
     */
    public function listNotes(Request $request, DataTableFactory $dataTableFactory): Response
    {
       $table = $dataTableFactory->create()
        ->add('id', TextColumn::class, ['label' => 'Id', 'searchable' => false])
        ->add('title', TextColumn::class, ['label' => 'Title', 'searchable' => false])
        ->add('text', TextColumn::class, ['label' => 'Text', 'data' => 'N/A', 'searchable' => true])
        ->add('created_at', DateTimeColumn::class, ['label' => 'Created At', 'searchable' => false])
        ->createAdapter(ORMAdapter::class, 
            [
                'entity' => Note::class,
                'query' => function (QueryBuilder $queryBuilder) {
                    $queryBuilder->select('e')
                    ->from(Note::class, 'e')
                    ->orderBy('e.created_at', 'DESC');
                }
            ]
        )
        ->handleRequest($request);
       
        // If AJAX call made from JS, then return content only, not rendered entity
        if ($table->isCallback()) {
            return $table->getResponse();
        }
      
        return $this->render(
            'grid/note_list.html.twig', 
            ['datatable' => $table]
        );
    }
}

