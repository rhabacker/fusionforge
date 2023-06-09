<?php
/**
 * Copyright 2011, Roland Mas
 * Copyright 2013,2019, Franck Villaume - TrivialDev
 * Copyright (C) 2015  Inria (Sylvain Beucler)
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published
 * by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

require_once dirname(dirname(__FILE__)).'/SeleniumForge.php';

class Search extends FForge_SeleniumTestCase
{
	public $fixture = 'projecta';

	function testSearch()
	{
		$this->loadAndCacheFixture();

		/*
		 * Search for projects
		 */
		$this->createProject('ProjectB');

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "XXXXXXXXXXXXXXXXXXXXXXXXXX");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertTrue($this->isTextPresent("No matches found for"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "projecta");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("public description for ProjectA"));
		$this->assertFalse($this->isTextPresent("public description for ProjectB"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "description public ProjectA");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("public description for ProjectA"));
		$this->assertFalse($this->isTextPresent("public description for ProjectB"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "description 'public ProjectA'");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertTrue($this->isTextPresent("No matches found for"));
		$this->assertFalse($this->isTextPresent("public description for ProjectA"));
		$this->assertFalse($this->isTextPresent("public description for ProjectB"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "description public");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("public description for ProjectA"));
		$this->assertTrue($this->isTextPresent("public description for ProjectB"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "'description public'");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertTrue($this->isTextPresent("No matches found for"));
		$this->assertFalse($this->isTextPresent("public description for ProjectA"));
		$this->assertFalse($this->isTextPresent("public description for ProjectB"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "'public description'");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("public description for ProjectA"));
		$this->assertTrue($this->isTextPresent("public description for ProjectB"));

		/*
		 * Test paging system
		 */
		for ($i = 1; $i <= 30; $i++) {
			$pname = sprintf("project-x%02d",$i);
			$this->createProject($pname);
		}

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "'public description'");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("public description for ProjectA"));
		$this->assertTrue($this->isTextPresent("public description for ProjectB"));
		$this->assertTrue($this->isTextPresent("public description for project-x15"));
		$this->assertFalse($this->isTextPresent("public description for project-x30"));
		$this->clickAndWait("link=Next Results");
		$this->assertFalse($this->isTextPresent("public description for project-x15"));
		$this->assertTrue($this->isTextPresent("public description for project-x30"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->type("//input[@name='words']", "x15");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("public description for project-x15"));
		$this->assertFalse($this->isTextPresent("public description for ProjectB"));

		/*
		 * Search for people
		 */

		$this->createUser('ratatouille');
		$this->createUser('tartiflette');

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("People");
		$this->type("//input[@name='words']", "tartempion");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertTrue($this->isTextPresent("No matches found for"));
		$this->assertFalse($this->isTextPresent("ratatouille Lastname"));
		$this->assertFalse($this->isTextPresent("tartiflette Lastname"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("People");
		$this->type("//input[@name='words']", "ratatouille");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("ratatouille Lastname"));
		$this->assertFalse($this->isTextPresent("tartiflette Lastname"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("People");
		$this->type("//input[@name='words']", "lastname ratatouille");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("ratatouille Lastname"));
		$this->assertFalse($this->isTextPresent("tartiflette Lastname"));

		$this->open(ROOT) ;
		$this->waitForPageToLoad();
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("People");
		$this->type("//input[@name='words']", "Lastname");
		$this->clickAndWait("//input[@name='Search']");
		$this->waitForPageToLoad();
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("ratatouille Lastname"));
		$this->assertTrue($this->isTextPresent("tartiflette Lastname"));

		/*
		 * Search inside a project
		 */

		// Prepare some tracker items

		$this->gotoProject('ProjectA');
		$this->clickAndWait("link=Tracker");
		$this->clickAndWait("link=Bugs");
		$this->clickAndWait("link=Submit New");
		$this->type("summary", "Bug1 boustrophédon");
		$this->type("details", "brebis outremanchienne");
		$this->clickAndWait("//form[@id='trackeraddform']//input[@type='submit']");
		$this->clickAndWait("link=Bug1 boustrophédon");
		$this->type("details", 'Ceci était une référence au « Génie des Alpages », rien à voir avec Charlie - also, ZONGO, and needle');
		$this->clickAndWait("submit");

		$this->clickAndWait("link=Tracker");
		$this->clickAndWait("link=Patches");
		$this->clickAndWait("link=Submit New");
		$this->type("summary", "Bug2 gratapouêt");
		$this->type("details", "cthulhu was here - also, ZONGO, and Charlie was here too");
		$this->clickAndWait("//form[@id='trackeraddform']//input[@type='submit']");

		// Search in trackers

		$this->select($this->byName("type_of_search"))->selectOptionByValue("trackers");
		$this->type("//input[@name='words']", "brebis");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Bug1"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("trackers");
		$this->type("//input[@name='words']", "alpages");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Bug1"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("trackers");
		$this->type("//input[@name='words']", "boustrophédon brebis alpages");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Bug1"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("trackers");
		$this->type("//input[@name='words']", "'boustrophédon brebis'");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("No matches found for"));
		$this->assertFalse($this->isTextPresent("Bug1"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("trackers");
		$this->type("//input[@name='words']", "boustrophédon cthulhu");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("No matches found for"));
		$this->assertFalse($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));

		// Search in one particular tracker

		$this->select($this->byName("type_of_search"))->selectOptionByValue("trackers");
		$this->type("//input[@name='words']", "charlie");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertTrue($this->isTextPresent("Bug2"));

		$this->clickAndWait("link=Tracker");
		$this->clickAndWait("link=Bugs");
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("Bugs");
		$this->type("//input[@name='words']", "charlie");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));

		$this->clickAndWait("link=Bugs");
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("Bugs");
		$this->type("//input[@name='words']", "charlie boustrophédon");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));

		// Create some tasks

		$this->gotoProject('ProjectA');
		$this->clickAndWait("link=Tasks");
		$this->clickAndWait("link=To Do");
		$this->clickAndWait("link=Add Task");
		$this->type("summary", "Task1 the brain");
		$this->type("details", "The same thing we do every night, Pinky - try to take over the world! - also, ZONGO");
		$this->type("hours", "199");
		$this->clickAndWait("submit");

		$this->clickAndWait("link=Task1 the brain");
		$this->type("followup", 'This is the needle for tasks');
		$this->clickAndWait("submit");

		$this->gotoProject('ProjectA');
		$this->clickAndWait("link=Tasks");
		$this->clickAndWait("link=Next Release");
		$this->clickAndWait("link=Add Task");
		$this->type("summary", "Task2 world peace");
		$this->type("details", "Otherwise WW4 will be fought with sticks - also, ZONGO");
		$this->type("hours", "199");
		$this->clickAndWait("submit");

		// Search in Tasks

		$this->select($this->byName("type_of_search"))->selectOptionByValue("tasks");
		$this->type("//input[@name='words']", "pinky");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Task1"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("tasks");
		$this->type("//input[@name='words']", "cortex");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("No matches found for"));
		$this->assertFalse($this->isTextPresent("Task1"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("tasks");
		$this->type("//input[@name='words']", "brain pinky needle");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Task1"));

		// Post some messages in a forum

		$this->gotoProject('ProjectA');
		$this->clickAndWait("link=Forums");
		$this->clickAndWait("link=open-discussion");
		$this->clickAndWait("link=Start New Thread");
		$this->waitForPageToLoad();
		$this->type("subject", "Message1 in a bottle");
		$this->type("body", "ninetynine of them on Charlie's wall - also, ZONGO");
		$this->clickAndWait("submit");
		$this->clickAndWait("link=Message1 in a bottle");
		$this->clickAndWait("link=[ Reply ]");
		$this->type("subject", "Message2 in a bottle");
		$this->type("body", "ninetyeight of them in Charlie's fridge - also, ZONGO");
		$this->clickAndWait("submit");
		$this->clickAndWait("link=Message1 in a bottle");
		$this->clickAndWait("link=[ Reply ]");
		$this->type("subject", "Message3 in a bottle");
		$this->type("body", "and yet another needle for the forums - also, ZONGO");
		$this->clickAndWait("submit");

		$this->clickAndWait("link=Forums");
		$this->clickAndWait("link=developers-discussion");
		$this->clickAndWait("link=Start New Thread");
		$this->waitForPageToLoad();
		$this->type("subject", "Message4 in an envelope");
		$this->type("body", "not the same thing as an antilope (and different thread anyway) (but still related to Charlie) - also, ZONGO");
		$this->clickAndWait("submit");

		// Search in Forums

		$this->select($this->byName("type_of_search"))->selectOptionByValue("forums");
		$this->type("//input[@name='words']", "bottle");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertTrue($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("forums");
		$this->type("//input[@name='words']", "bottle fridge");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertFalse($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertFalse($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));

		// Search in one particular forum

		$this->select($this->byName("type_of_search"))->selectOptionByValue("forums");
		$this->type("//input[@name='words']", "charlie");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertFalse($this->isTextPresent("Message3"));
		$this->assertTrue($this->isTextPresent("Message4"));

		$this->clickAndWait("link=Forums");
		$this->clickAndWait("link=open-discussion");
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("This forum");
		$this->type("//input[@name='words']", "charlie");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertFalse($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));

		$this->clickAndWait("link=Forums");
		$this->clickAndWait("link=open-discussion");
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("This forum");
		$this->type("//input[@name='words']", "charlie fridge");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		// Only one result => threaded view => need to check on bodies, not subjects
		$this->assertFalse($this->isTextPresent("wall"));
		$this->assertTrue($this->isTextPresent("fridge"));
		$this->assertFalse($this->isTextPresent("needle"));
		$this->assertFalse($this->isTextPresent("Message4"));

		// Create some documents

		$this->gotoProject('ProjectA');
		$this->clickAndWait("link=Docs");
		$this->clickAndWait("id=addItemDocmanMenu");
		// ugly hack until we fix behavior in docman when no folders exist. We need to click twice on the link
		$this->clickAndWait("id=addItemDocmanMenu");
		$this->clickAndWait("jquery#tab-new-document");
		$this->type("title", "Doc1 Vladimir");
		$this->type("//textarea[@name='description']", "Jenkins buildbot - also, ZONGO");
		$this->clickAndWait("//input[@name='type' and @value='pasteurl']");
		$this->type("file_url", "http://buildbot.fusionforge.org/");
		$this->clickAndWait("submit");

		$this->clickAndWait("id=addItemDocmanMenu");
		$this->clickAndWait("jquery#tab-new-document");
		$this->type("title", "Doc2 Astromir");
		$this->type("//textarea[@name='description']", "Main website (the needle) - also, ZONGO");
		$this->clickAndWait("//input[@name='type' and @value='pasteurl']");
		$this->type("file_url", "http://fusionforge.org/");
		$this->clickAndWait("submit");

		// Search in Documents

		$this->select($this->byName("type_of_search"))->selectOptionByValue("alldocs");
		$this->type("//input[@name='words']", "jenkins");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Doc1"));
		$this->assertFalse($this->isTextPresent("Doc2"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("alldocs");
		$this->type("//input[@name='words']", "vladimir jenkins");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("Doc1"));
		$this->assertFalse($this->isTextPresent("Doc2"));

		// Create some news

		$this->gotoProject('ProjectA');
		$this->clickAndWait("link=News");
		$this->clickAndWait("link=Submit");
		$this->type("summary", "News1 daily planet");
		$this->type("details", "Clark Kent's newspaper - also, ZONGO");
		$this->clickAndWait("submit");

		$this->clickAndWait("link=Submit");
		$this->type("summary", "News2 usenet");
		$this->type("details", "alt sysadmin recovery (needle) - also, ZONGO");
		$this->clickAndWait("submit");
		$this->clickAndWait("link=News");

		// Search in news

		$this->select($this->byName("type_of_search"))->selectOptionByValue("news");
		$this->type("//input[@name='words']", "sysadmin");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("News2"));

		$this->select($this->byName("type_of_search"))->selectOptionByValue("news");
		$this->type("//input[@name='words']", "daily newspaper");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("No matches found for"));
		$this->assertTrue($this->isTextPresent("News1"));

		// Search in entire project
		$this->gotoProject('ProjectA');
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("Search the entire project");
		$this->type("//input[@name='words']", "needle");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));
		$this->assertTrue($this->isTextPresent("Task1"));
		$this->assertFalse($this->isTextPresent("Task2"));
		$this->assertFalse($this->isTextPresent("Message1"));
		$this->assertFalse($this->isTextPresent("Message2"));
		$this->assertTrue($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));
		$this->assertFalse($this->isTextPresent("Doc1"));
		$this->assertTrue($this->isTextPresent("Doc2"));
		$this->assertFalse($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		$this->gotoProject('ProjectA');
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("Search the entire project");
		$this->type("//input[@name='words']", "zongo");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertTrue($this->isTextPresent("Bug2"));
		$this->assertTrue($this->isTextPresent("Task1"));
		$this->assertTrue($this->isTextPresent("Task2"));
		$this->assertTrue($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertTrue($this->isTextPresent("Message3"));
		$this->assertTrue($this->isTextPresent("Message4"));
		$this->assertTrue($this->isTextPresent("Doc1"));
		$this->assertTrue($this->isTextPresent("Doc2"));
		$this->assertTrue($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		// Advanced search

		$this->gotoProject('ProjectA');
		$this->clickAndWait('link=Advanced search');
		$this->clickAndWait("//input[@class='checkthemall']");
		$this->type("//main[@id='maindiv']//input[@name='words']", "needle");
		$this->clickAndWait("//input[@name='submitbutton']");
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));
		$this->assertTrue($this->isTextPresent("Task1"));
		$this->assertFalse($this->isTextPresent("Task2"));
		$this->assertFalse($this->isTextPresent("Message1"));
		$this->assertFalse($this->isTextPresent("Message2"));
		$this->assertTrue($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));
		$this->assertFalse($this->isTextPresent("Doc1"));
		$this->assertTrue($this->isTextPresent("Doc2"));
		$this->assertFalse($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		$this->gotoProject('ProjectA');
		$this->clickAndWait('link=Advanced search');
		$this->clickAndWait("//input[@class='checkthemall']");
		$this->type("//main[@id='maindiv']//input[@name='words']", "zongo");
		$this->clickAndWait("//input[@name='submitbutton']");
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertTrue($this->isTextPresent("Bug2"));
		$this->assertTrue($this->isTextPresent("Task1"));
		$this->assertTrue($this->isTextPresent("Task2"));
		$this->assertTrue($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertTrue($this->isTextPresent("Message3"));
		$this->assertTrue($this->isTextPresent("Message4"));
		$this->assertTrue($this->isTextPresent("Doc1"));
		$this->assertTrue($this->isTextPresent("Doc2"));
		$this->assertTrue($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		// Now let's check that RBAC permissions are taken into account

		$this->gotoProject('ProjectA');
		$this->clickAndWait("link=Admin");
		$this->waitForPageToLoad();
		$this->clickAndWait("link=Users and permissions");
		$this->waitForPageToLoad();
		$this->clickAndWait("//tr/td/form/div[contains(.,'Any user logged in')]/../../../td/form/div/input[contains(@value,'Unlink Role')]");
		$this->waitForPageToLoad();
		$this->type ("//form[contains(@action,'roleedit.php')]/..//input[@name='role_name']", "Trainee") ;
		$this->clickAndWait("//input[@value='Create Role']") ;
		$this->waitForPageToLoad();

		$this->clickAndWait("link=Users and permissions");
		$this->waitForPageToLoad();
		$this->type ("//form[contains(@action,'users.php')]//input[@name='form_unix_name' and @type='text']", "ratatouille") ;
		$this->select($this->byXPath("//input[@value='Add Member']/../fieldset/select[@name='role_id']"))->selectOptionByLabel("Trainee");
		$this->clickAndWait("//input[@value='Add Member']") ;
		$this->waitForPageToLoad();

		$this->clickAndWait("link=Users and permissions");
		$this->waitForPageToLoad();
		$this->clickAndWait("//td/form/div[contains(.,'Trainee')]/../div/input[@value='Edit Permissions']") ;
		$this->waitForPageToLoad();
		$this->select($this->byXPath("//select[contains(@name,'data[project_read]')]"))->selectOptionByLabel("Visible");
		$this->select($this->byXPath("//tr/td[.='Bugs']/../td/fieldset/select[contains(@name,'data[tracker]')]"))->selectOptionByLabel("Read only");
		$this->select($this->byXPath("//tr/td[.='Patches']/../td/fieldset/select[contains(@name,'data[tracker]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//tr/td[.='To Do']/../td/fieldset/select[contains(@name,'data[pm]')]"))->selectOptionByLabel("Read only");
		$this->select($this->byXPath("//tr/td[.='Next Release']/../td/fieldset/select[contains(@name,'data[pm]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//tr/td[.='open-discussion']/../td/fieldset/select[contains(@name,'data[forum]')]"))->selectOptionByLabel("Read only");
		$this->select($this->byXPath("//tr/td[.='developers-discussion']/../td/fieldset/select[contains(@name,'data[forum]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//select[contains(@name,'data[docman]')]"))->selectOptionByLabel("Read only");
		$this->clickAndWait("//input[@value='Submit']") ;
		$this->waitForPageToLoad();

		$this->clickAndWait("link=Users and permissions");
		$this->waitForPageToLoad();
		$this->clickAndWait("//td/form/div[contains(.,'Anonymous')]/../div/input[@value='Edit Permissions']") ;
		$this->waitForPageToLoad();
		$this->select($this->byXPath("//select[contains(@name,'data[project_read]')]"))->selectOptionByLabel("Visible");
		$this->select($this->byXPath("//tr/td[.='Bugs']/../td/fieldset/select[contains(@name,'data[tracker]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//tr/td[.='Patches']/../td/fieldset/select[contains(@name,'data[tracker]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//tr/td[.='To Do']/../td/fieldset/select[contains(@name,'data[pm]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//tr/td[.='Next Release']/../td/fieldset/select[contains(@name,'data[pm]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//tr/td[.='open-discussion']/../td/fieldset/select[contains(@name,'data[forum]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//tr/td[.='developers-discussion']/../td/fieldset/select[contains(@name,'data[forum]')]"))->selectOptionByLabel("No Access");
		$this->select($this->byXPath("//select[contains(@name,'data[docman]')]"))->selectOptionByLabel("No Access");
		$this->clickAndWait("//input[@value='Submit']") ;
		$this->waitForPageToLoad();

		$this->switchUser('ratatouille');
		$this->gotoProject('ProjectA');
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("Search the entire project");
		$this->type("//input[@name='words']", "zongo");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));
		$this->assertTrue($this->isTextPresent("Task1"));
		$this->assertFalse($this->isTextPresent("Task2"));
		$this->assertTrue($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertTrue($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));
		$this->assertTrue($this->isTextPresent("Doc1"));
		$this->assertTrue($this->isTextPresent("Doc2"));
		$this->assertTrue($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		$this->gotoProject('ProjectA');
		$this->clickAndWait('link=Advanced search');
		$this->clickAndWait("//input[@class='checkthemall']");
		$this->type("//main[@id='maindiv']//input[@name='words']", "zongo");
		$this->clickAndWait("//input[@name='submitbutton']");
		$this->assertTrue($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));
		$this->assertTrue($this->isTextPresent("Task1"));
		$this->assertFalse($this->isTextPresent("Task2"));
		$this->assertTrue($this->isTextPresent("Message1"));
		$this->assertTrue($this->isTextPresent("Message2"));
		$this->assertTrue($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));
		$this->assertTrue($this->isTextPresent("Doc1"));
		$this->assertTrue($this->isTextPresent("Doc2"));
		$this->assertTrue($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		$this->logout();
		$this->gotoProject('ProjectA');
		$this->select($this->byName("type_of_search"))->selectOptionByLabel("Search the entire project");
		$this->type("//input[@name='words']", "zongo");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));
		$this->assertFalse($this->isTextPresent("Task1"));
		$this->assertFalse($this->isTextPresent("Task2"));
		$this->assertFalse($this->isTextPresent("Message1"));
		$this->assertFalse($this->isTextPresent("Message2"));
		$this->assertFalse($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));
		$this->assertFalse($this->isTextPresent("Doc1"));
		$this->assertFalse($this->isTextPresent("Doc2"));
		$this->assertTrue($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		$this->gotoProject('ProjectA');
		$this->clickAndWait('link=Advanced search');
		$this->clickAndWait("//input[@class='checkthemall']");
		$this->assertFalse($this->isElementPresent("//input[@name='short_pm_checkall']"));
		$this->assertFalse($this->isElementPresent("//input[@name='short_docman_checkall']"));
		$this->type("//main[@id='maindiv']//input[@name='words']", "zongo");
		$this->clickAndWait("//input[@name='submitbutton']");
		$this->assertFalse($this->isTextPresent("Bug1"));
		$this->assertFalse($this->isTextPresent("Bug2"));
		$this->assertFalse($this->isTextPresent("Task1"));
		$this->assertFalse($this->isTextPresent("Task2"));
		$this->assertFalse($this->isTextPresent("Message1"));
		$this->assertFalse($this->isTextPresent("Message2"));
		$this->assertFalse($this->isTextPresent("Message3"));
		$this->assertFalse($this->isTextPresent("Message4"));
		$this->assertFalse($this->isTextPresent("Doc1"));
		$this->assertFalse($this->isTextPresent("Doc2"));
		$this->assertTrue($this->isTextPresent("News1"));
		$this->assertTrue($this->isTextPresent("News2"));

		// Test language-specific search configurations
		// Also test project search based on tags
		$this->createAndGoto('projectc');
		$this->clickAndWait("link=Admin");
		$this->type("//textarea[@name='form_shortdesc']","This is the public description for projectc. It's about Coding hôtels Iiİı.");
		$this->type("//input[@name='form_tags']","Goldorak");
		$this->clickAndWait("//input[@name='submit']");

		// In "simple" configuration, no normalization occurs beyond capitalization
		$this->runCommand(dirname(__FILE__).'/../../../src/bin/configure-fti-search.php simple');
		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "coded");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("public description for projectc"));

		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "goldorak");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("public description for projectc"));

		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "hotel");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("public description for projectc"));

		// In English, accents are removed and coding==coded
		$this->runCommand(dirname(__FILE__).'/../../../src/bin/configure-fti-search.php english');
		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "coded");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("public description for projectc"));

		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "goldorak");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("public description for projectc"));

		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "hotel");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("public description for projectc"));

		// In French, accents are removed but coding==coded
		$this->runCommand(dirname(__FILE__).'/../../../src/bin/configure-fti-search.php french');
		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "coded");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertFalse($this->isTextPresent("public description for projectc"));

		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "goldorak");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("public description for projectc"));

		$this->open(ROOT) ;
		$this->type("//input[@name='words']", "hotel");
		$this->clickAndWait("//input[@name='Search']");
		$this->assertTrue($this->isTextPresent("public description for projectc"));
	}
}
