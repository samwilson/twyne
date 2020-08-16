<?php

namespace Samwilson\Twyne\Controller;

use Samwilson\Twyne\Data\Contact;
use Samwilson\Twyne\Data\Post;
use Samwilson\Twyne\Template;

class PostController extends ControllerBase
{
    public function viewGet($args)
    {
        $tpl = $this->getTemplate('post.html');
        $post = Post::loadById($args['id']);
        $tpl->title = $post->getTitle();
        $tpl->post = $post;
        $this->outputTemplate($tpl);
    }

    public function editGet($args)
    {
        $tpl = $this->getTemplate('post_edit.html');
        if (!$this->user) {
            $this->addAlert('warning', 'not-authorized');
            $tpl->post = new Post();
            $this->outputTemplate($tpl);
            return;
        }
        $id = $args['id'] ?? null;
        $post = $id ? Post::loadById($id) : Post::newForUser($this->user);
        if (!$post->canBeEditedBy($this->user)) {
            $this->addAlert('warning', 'not-authorized');
        }
        $tpl->post = $post;
        $this->outputTemplate($tpl);
    }

    public function savePost()
    {
        if (!$this->user) {
            $this->addAlert('warning', 'not-authorized');
            $this->redirect('/new');
        }
        $id = $this->getParamPost('id');
        $post = $id ? Post::loadById($id) : Post::newForUser($this->user);
        if (!$post->canBeEditedBy($this->user)) {
            $this->addAlert('warning', 'not-authorized');
            $this->redirect('/' . ($id ? "$id/edit" : 'new'));
        }
        $post->setAuthor($this->user->getContact());
        if ($this->getParamPost('author')) {
            $author = Contact::loadById($this->getParamPost('author'));
            if ($author->exists()) {
                $post->setAuthor($author);
            }
        }
        $post->setDatetime($this->getParamPost('datetime'));
        $post->setBody($this->getParamPost('body'));
        $post->save();
        $this->addAlert(Template::INFO, 'saved');
        $this->redirect('/' . $post->getId());
    }
}
