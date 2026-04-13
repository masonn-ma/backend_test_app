<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Test Controller
 *
 */
class TestController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Audit');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $tests = $this->paginate($this->Test);
        $this->set(compact('tests'));
    }

    /**
     * View method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $testEntity = $this->Test->get($id, contain: []);
        $this->Audit->logView($id);

        $this->set(compact('testEntity'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $testEntity = $this->Test->newEmptyEntity();

        if ($this->request->is('post')) {
            $testEntity = $this->Test->patchEntity($testEntity, $this->request->getData());

            if ($this->Test->save($testEntity)) {
                $this->Audit->logCreate($testEntity->id, $this->request->getData());
                $this->Flash->success(__('The test has been saved.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The test could not be saved. Please, try again.'));
        }

        $this->set(compact('testEntity'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $testEntity = $this->Test->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $testEntity = $this->Test->patchEntity($testEntity, $this->request->getData());
            if ($this->Test->save($testEntity)) {
                $this->Audit->logUpdate($id, $this->request->getData());
                $this->Flash->success(__('The test has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The test could not be saved. Please, try again.'));
        }
        $this->set(compact('testEntity'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $testEntity = $this->Test->get($id);
        if ($this->Test->delete($testEntity)) {
            $this->Audit->logDelete($id);
            $this->Flash->success(__('The test has been deleted.'));
        } else {
            $this->Flash->error(__('The test could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
