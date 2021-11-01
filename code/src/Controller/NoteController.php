<?php
/**
 * @author Scott Anderson <94andersonsc@googlemail.com
 * @license MIT
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Note;
use App\Repository\NoteRepository;
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

class NoteController extends AbstractController
{       
    const UPDATEABALE_FIELDS = ['title', 'text'];
    
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
        
        return new Response(
            sprintf('Saved new note with id %s', $note->getId()), 
            Response::HTTP_CREATED,
            ['content-type' => 'text/html']
        );
    }
    
    /**
     * @Route("/notes/{id}", name="get_note", methods={"GET"})
     * 
     * @param int $id
     * 
     * @return Response
     */
    public function getNoteById(int $id): Response 
    {
        $note = $this->getDoctrine()
        ->getRepository(Note::class)
        ->find($id);
        
        if ($note === null) {
            throw new EntityNotFoundException(sprintf('No such entity found for id \'%s\'', $id));
        }
        
        return $this->render(
            'note/note_info.html.twig',
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
        } else if (!count(array_intersect(static::UPDATEABALE_FIELDS, array_values($params)))) {
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
        
        $view = $this->renderView(
            'note/note_info.html.twig',
            [
                'action' => 'Updated',
                'note_title' => $note->getTitle(),
                'note_text' => $note->getText()
            ]
        );
        
        return new Response(
            $view, 
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        ); 
    }
    
    
   /**
    * @Route("/notes/{id}", name="delete_note", methods={"DELETE"})
    * @param int $id
    * 
    * @return Response
    */
    public function deleteNoteById(int $id): Response
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
       
        if ($table->isCallback()) {
            return $table->getResponse();
        }
      
        return $this->render(
            'grid/note_list.html.twig', 
            ['datatable' => $table]
        );
    }
}

