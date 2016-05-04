<?php

final class DiffusionRepositoryStagingManagementPanel
  extends DiffusionRepositoryManagementPanel {

  const PANELKEY = 'staging';

  public function getManagementPanelLabel() {
    return pht('Staging Area');
  }

  public function getManagementPanelOrder() {
    return 700;
  }

  protected function buildManagementPanelActions() {
    $repository = $this->getRepository();
    $viewer = $this->getViewer();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $repository,
      PhabricatorPolicyCapability::CAN_EDIT);

    $staging_uri = $repository->getPathURI('edit/staging/');

    return array(
      id(new PhabricatorActionView())
        ->setIcon('fa-pencil')
        ->setName(pht('Edit Staging'))
        ->setHref($staging_uri)
        ->setDisabled(!$can_edit)
        ->setWorkflow(!$can_edit),
    );
  }

  public function buildManagementPanelContent() {
    $repository = $this->getRepository();
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setViewer($viewer)
      ->setActionList($this->newActions());

    $staging_uri = $repository->getStagingURI();
    if (!$staging_uri) {
      $staging_uri = phutil_tag('em', array(), pht('No Staging Area'));
    }

    $view->addProperty(pht('Staging Area URI'), $staging_uri);

    return $this->newBox(pht('Staging Area'), $view);
  }

}
