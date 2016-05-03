CREATE OR REPLACE FUNCTION update_vectors() RETURNS TRIGGER AS '
DECLARE
table_name TEXT;
BEGIN
	table_name := TG_ARGV[0];
	-- **** artifact table ****
	IF table_name = ''artifact'' THEN
		IF TG_OP = ''DELETE'' THEN
		      DELETE FROM artifact_idx WHERE artifact_id=OLD.artifact_id;
		ELSE
		      DELETE FROM artifact_idx WHERE artifact_id=NEW.artifact_id;
		      INSERT INTO artifact_idx (SELECT a.artifact_id, to_tsvector(a.artifact_id::text) || to_tsvector(a.summary) || to_tsvector(a.details) || coalesce(ff_tsvector_agg(to_tsvector(am.body)), to_tsvector('''')) AS vectors FROM artifact a LEFT OUTER JOIN artifact_message am USING (artifact_id) WHERE a.artifact_id=NEW.artifact_id GROUP BY a.artifact_id, a.summary, a.details);
		END IF;
	-- **** artifact_message table ****
	ELSIF table_name = ''artifact_message'' THEN
		IF TG_OP = ''DELETE'' THEN
		      DELETE FROM artifact_idx WHERE artifact_id=OLD.artifact_id;
		ELSE
		      DELETE FROM artifact_idx WHERE artifact_id=NEW.artifact_id;
		      INSERT INTO artifact_idx (SELECT a.artifact_id, to_tsvector(a.artifact_id::text) || to_tsvector(a.summary) || to_tsvector(a.details) || coalesce(ff_tsvector_agg(to_tsvector(am.body)), to_tsvector('''')) AS vectors FROM artifact a LEFT OUTER JOIN artifact_message am USING (artifact_id) WHERE a.artifact_id=NEW.artifact_id GROUP BY a.artifact_id, a.summary, a.details);
		END IF;
	-- **** doc_data table ****
	ELSIF table_name = ''doc_data'' THEN
		IF TG_OP = ''INSERT'' THEN
			INSERT INTO doc_data_idx (docid, group_id, vectors) VALUES (NEW.docid, NEW.group_id, to_tsvector(coalesce(NEW.title,'''') ||'' ''|| coalesce(NEW.description,'''')));
		ELSIF TG_OP = ''UPDATE'' THEN
			UPDATE doc_data_idx SET group_id=NEW.group_id, vectors=to_tsvector(coalesce(NEW.title,'''') ||'' ''|| coalesce(NEW.description,'''')) WHERE docid=NEW.docid;
		ELSIF TG_OP = ''DELETE'' THEN
			DELETE FROM doc_data_idx WHERE docid=OLD.docid;
		END IF;
	-- **** forum table ****
	ELSIF table_name = ''forum'' THEN
		IF TG_OP = ''INSERT'' THEN
			INSERT INTO forum_idx (msg_id, group_id, vectors) (SELECT f.msg_id, g.group_id, to_tsvector(coalesce(f.subject,'''') ||'' ''|| 
			coalesce(f.body,'''')) AS vectors FROM forum f, forum_group_list g WHERE f.group_forum_id = g.group_forum_id AND f.msg_id = NEW.msg_id);
		ELSIF TG_OP = ''UPDATE'' THEN
			UPDATE forum_idx SET vectors=to_tsvector(coalesce(NEW.subject,'''') ||'' ''|| coalesce(NEW.body,'''')) WHERE msg_id=NEW.msg_id;
		ELSIF TG_OP = ''DELETE'' THEN
			DELETE FROM forum_idx WHERE msg_id=OLD.msg_id;
		END IF;
	-- **** frs_file table ****
	ELSIF table_name = ''frs_file'' THEN
		IF TG_OP = ''INSERT'' THEN
			INSERT INTO frs_file_idx (file_id, release_id, vectors) VALUES (NEW.file_id, NEW.release_id, to_tsvector(coalesce(NEW.filename,'''')));
		ELSIF TG_OP = ''UPDATE'' THEN
			UPDATE frs_file_idx SET vectors=to_tsvector(coalesce(NEW.filename,'''')), release_id=NEW.release_id WHERE file_id=NEW.file_id;
		ELSIF TG_OP = ''DELETE'' THEN
			DELETE FROM frs_file_idx WHERE file_id=OLD.file_id;
		END IF;
	-- **** frs_release table ****
	ELSIF table_name = ''frs_release'' THEN
		IF TG_OP = ''INSERT'' THEN
			INSERT INTO frs_release_idx (release_id, vectors) VALUES (NEW.release_id, to_tsvector(coalesce(NEW.changes,'''') ||'' ''|| coalesce(NEW.notes,'''') ||'' ''|| coalesce(NEW.name,'''')));
		ELSIF TG_OP = ''UPDATE'' THEN
			UPDATE frs_release_idx SET vectors=to_tsvector(coalesce(NEW.changes,'''') ||'' ''|| coalesce(NEW.notes,'''') ||'' ''|| coalesce(NEW.name,'''')) WHERE release_id=NEW.release_id;
		ELSIF TG_OP = ''DELETE'' THEN
			DELETE FROM frs_release_idx WHERE release_id=OLD.release_id;
			DELETE FROM frs_file_idx WHERE release_id=OLD.release_id;
		END IF;
	-- **** groups table ****
	ELSIF table_name = ''groups'' THEN
		IF TG_OP = ''DELETE'' THEN
			DELETE FROM groups_idx WHERE group_id=OLD.group_id;
		ELSE
			DELETE FROM groups_idx WHERE group_id=NEW.group_id;
			INSERT INTO groups_idx (group_id, vectors) (SELECT g.group_id, to_tsvector(coalesce(g.group_name,'''') ||'' ''|| coalesce(g.short_description,'''') ||'' ''|| coalesce(g.unix_group_name,'''')||'' ''|| coalesce(ff_tsvector_agg(to_tsvector(t.name)),to_tsvector(''''))) FROM groups g LEFT OUTER JOIN project_tags t USING (group_id) WHERE g.group_id = NEW.group_id GROUP BY g.group_id ORDER BY g.group_id);
		END IF;
	-- **** news_bytes table ****
	ELSIF table_name = ''news_bytes'' THEN
		IF TG_OP = ''INSERT'' THEN
			INSERT INTO news_bytes_idx (id, vectors) VALUES (NEW.id, to_tsvector(coalesce(NEW.summary,'''') ||'' ''|| coalesce(NEW.details,'''')));
		ELSIF TG_OP = ''UPDATE'' THEN
			UPDATE news_bytes_idx SET vectors=to_tsvector(coalesce(NEW.summary,'''') ||'' ''|| coalesce(NEW.details,'''')) WHERE id=NEW.id;
		ELSIF TG_OP = ''DELETE'' THEN
			DELETE FROM news_bytes_idx WHERE id=OLD.id;
		END IF;
	-- **** project_task table ****
	ELSIF table_name = ''project_task'' THEN
		IF TG_OP = ''DELETE'' THEN
			DELETE FROM project_task_idx WHERE project_task_id=OLD.project_task_id;
		ELSE
			DELETE FROM project_task_idx WHERE project_task_id=NEW.project_task_id;
			INSERT INTO project_task_idx (SELECT t.project_task_id, to_tsvector(t.project_task_id::text) || to_tsvector(t.summary) || to_tsvector(t.details) || coalesce(ff_tsvector_agg(to_tsvector(tm.body)), to_tsvector('''')) AS vectors FROM project_task t LEFT OUTER JOIN project_messages tm USING (project_task_id) WHERE t.project_task_id=NEW.project_task_id GROUP BY t.project_task_id, t.summary, t.details);
		END IF;
	-- **** project_messages table ****
	ELSIF table_name = ''project_messages'' THEN
		IF TG_OP = ''DELETE'' THEN
			DELETE FROM project_task_idx WHERE project_task_id=OLD.project_task_id;
		ELSE
			DELETE FROM project_task_idx WHERE project_task_id=NEW.project_task_id;
			INSERT INTO project_task_idx (SELECT t.project_task_id, to_tsvector(t.summary) || to_tsvector(t.details) || coalesce(ff_tsvector_agg(to_tsvector(tm.body)), to_tsvector('''')) AS vectors FROM project_task t LEFT OUTER JOIN project_messages tm USING (project_task_id) WHERE t.project_task_id=NEW.project_task_id GROUP BY t.project_task_id, t.summary, t.details);
		END IF;
	-- **** skills_data table ****
	ELSIF table_name = ''skills_data'' THEN
		IF TG_OP = ''INSERT'' THEN
			INSERT INTO skills_data_idx (skills_data_id, vectors) VALUES (NEW.skill_data_id, to_tsvector(coalesce(NEW.title,'''') ||'' ''|| coalesce(NEW.keywords,'''')));
		ELSIF TG_OP = ''UPDATE'' THEN
			UPDATE skills_data_idx SET vectors=to_tsvector(coalesce(NEW.title,'''') ||'' ''|| coalesce(NEW.keywords,'''')) WHERE skills_data_id=NEW.skills_data_id;
		ELSIF TG_OP = ''DELETE'' THEN
			DELETE FROM skills_data_idx WHERE skills_data_id=OLD.skills_data_id;
		END IF;
	-- **** users table ****
	ELSIF table_name = ''users'' THEN
		IF TG_OP = ''INSERT'' THEN
			INSERT INTO users_idx (user_id, vectors) VALUES (NEW.user_id, to_tsvector(coalesce(NEW.user_name,'''') ||'' ''|| coalesce(NEW.realname,'''')));
		ELSIF TG_OP = ''UPDATE'' THEN
			UPDATE users_idx SET vectors=to_tsvector(coalesce(NEW.user_name,'''') ||'' ''|| coalesce(NEW.realname,'''')) WHERE user_id=NEW.user_id;
		ELSIF TG_OP = ''DELETE'' THEN
			DELETE FROM users_idx WHERE user_id=OLD.user_id;
		END IF;
	END IF;

	RETURN NEW;
END;'
LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION rebuild_fti_indices() RETURNS void AS $$
BEGIN
       UPDATE groups SET short_description=short_description;
       UPDATE artifact SET summary=summary;
       UPDATE artifact_message SET body=body;
       UPDATE doc_data SET title=title;
       UPDATE forum SET subject=subject;
       UPDATE frs_file SET filename=filename;
       UPDATE frs_release SET name=name;
       UPDATE news_bytes SET summary=summary;
       UPDATE project_task SET summary=summary;
       UPDATE project_messages SET body=body;
       UPDATE skills_data SET keywords=keywords;
       UPDATE users SET realname=realname;
END;
$$ LANGUAGE 'plpgsql';

-- Rebuild all indices
SELECT rebuild_fti_indices();
