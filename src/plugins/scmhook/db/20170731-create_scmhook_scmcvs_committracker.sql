CREATE TABLE plugin_scmhook_scmcvs_committracker_data_artifact (
        id                      serial PRIMARY KEY,
        kind                    integer DEFAULT '0' NOT NULL,
        group_artifact_id       integer,
        project_task_id         integer
);

CREATE TABLE plugin_scmhook_scmcvs_committracker_data_master (
        id              serial PRIMARY KEY,
        holder_id       integer NOT NULL,
        cvs_date        integer NOT NULL,
        log_text        text DEFAULT '',
        file            text DEFAULT '' NOT NULL,
        prev_version    text DEFAULT '',
        actual_version  text DEFAULT '',
        author          text DEFAULT '' NOT NULL
);
