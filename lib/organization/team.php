<?php
/*******************************************************************************
* Copyright (c) 2015 Eclipse Foundation and others.
* All rights reserved. This program and the accompanying materials
* are made available under the terms of the Eclipse Public License v1.0
* which accompanies this distribution, and is available at
* http://www.eclipse.org/legal/epl-v10.html
*
* Contributors:
*    Denis Roy (Eclipse Foundation) - initial API and implementation
*    Zak James (zak.james@gmail.com)
*******************************************************************************/

class Team {
	private $teamName;
	private $repoList;
	private $committerList;
	
	function __construct($teamName) {
		$this->teamName = $teamName;
		$this->teamID = 0;
		$this->repoList = array();
		$this->committerList = array();
	}
	
	function addRepo($repoUrl) {
		array_push($this->repoList, $repoUrl);
	}
	function addCommitter($committerEmail) {
		array_push($this->committerList, $committerEmail);
	}

	
	public function setTeamID($id) {
		$this->teamID = $id;
	}
	public function getTeamName() {
		return $this->teamName;
	}
	public function getTeamID() {
		return $this->teamID;
	}
	public function getRepoList() {
		return $this->repoList;
	}
	public function getCommitterList() {
		return $this->committerList;
	}
	public function clearCommitterList() {
		$this->committerList = array();
	}
	function debug() {
		print_r($this);
	}
}
?>