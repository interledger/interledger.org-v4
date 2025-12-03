.PHONY: info deploy backup restore list-backups deploy-staging deploy-production backup-production copy-production-to-staging promote-staging-to-production check-env-file

info:
	@echo "Makefile for CI/CD tasks for interledger.org website"
	@echo ""
	@echo "Available tasks:"
	@echo "  deploy ENV=<environment> RUN=<run-identifier>       Deploy php files to the specified environment (staging or production)"
	@echo "  backup ENV=<environment> RUN=<run-identifier>       Backup the specified environment database and files"
	@echo "  restore ENV=<environment> FROM-RUN=<run-identifier> TARGETENV=<target-environment>    Restore a backup to the specified target environment"
	@echo "  list-backups ENV=<environment>                      List available backups for the specified environment"

check-env-file:
	@if [ ! -f "backupmanager/cli/.env" ]; then \
		echo "Error: .env file not found at ci/backupmanager/cli/.env"; \
		echo ""; \
		echo "Please create one based on the sample.env file:"; \
		echo "  cp ci/backupmanager/cli/sample.env ci/backupmanager/cli/.env"; \
		echo ""; \
		echo "Then edit ci/backupmanager/cli/.env with your actual configuration values."; \
		echo ""; \
		echo "Required variables:"; \
		echo "  - GCP_PROJECT_ID"; \
		echo "  - BACKUP_BUCKET"; \
		echo "  - DB_NAME_STAGING, DB_NAME_PRODUCTION"; \
		echo "  - CLOUDSQL_INSTANCE_STAGING, CLOUDSQL_INSTANCE_PRODUCTION"; \
		echo "  - TARGET_HOST_STAGING, TARGET_HOST_PRODUCTION"; \
		echo "  - TARGET_USER_STAGING, TARGET_USER_PRODUCTION"; \
		echo "  - TARGET_PATH_STAGING, TARGET_PATH_PRODUCTION"; \
		exit 1; \
	fi


deploy:
ifndef ENV
	$(error Error: ENV variable is not set. Usage: make deploy ENV=<environment> RUN=<run-identifier>)
endif
ifndef RUN
	$(eval RUN=$(shell hostname)-$(shell date +%Y%m%d))
endif
	@echo "Deploying to $(ENV) environment with run identifier: $(RUN)"
	$(eval TARGETUSER=deployer)	
	$(eval TARGETHOST=34.23.109.31)	
ifeq ($(ENV),staging)
	$(eval TARGETFOLDER=/var/www/staging)
	$(eval TARGETENV=staging)
	rsync ../ci/deploy/$(ENV)/htaccess $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/web/.htaccess
	rsync ../ci/deploy/$(ENV)/robots.txt $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/web/robots.txt	
else ifeq ($(ENV),production)
	$(eval TARGETFOLDER=/var/www/production)
	$(eval TARGETENV=production)
else
	$(error Error: ENV variable must be either 'staging' or 'production')
endif
	@echo "Syncing files to $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)..."
	rsync -avz --exclude=backupmanager --exclude='local' --exclude='web/sites/default/files' --exclude='vendor' --exclude='docker' --exclude='tmp' --exclude='e2e' --exclude='ci/' --exclude='.git/' --exclude='README.md' ../ $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/
	rsync ../ci/deploy/$(ENV)/settings.php $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/web/sites/default/settings.php
	@echo "Running cleanup script on $(TARGETUSER)@$(TARGETHOST)..."
	scp ../ci/deploy/$(ENV)/cleanup.sh $(TARGETUSER)@$(TARGETHOST):$(TARGETFOLDER)/cleanup.sh
	ssh $(TARGETUSER)@$(TARGETHOST) "cd $(TARGETFOLDER) && chmod +x cleanup.sh && ./cleanup.sh"
	@echo "Deployment to $(ENV) environment completed."

backup: check-env-file
ifndef ENV
	$(error Error: ENV variable is not set. Usage: make backup ENV=<environment> RUN=<run-identifier>)
endif
ifndef RUN
	$(eval RUN=$(shell hostname)-$(shell date +%Y%m%d-%H%M%S))
endif
ifeq ($(ENV),staging)
else ifeq ($(ENV),production)
else
	$(error Error: ENV variable must be either 'staging' or 'production')
endif
	@echo "Backing up $(ENV) environment with run identifier: $(RUN)..."
	@echo "This assumes you are authenticated with GCP (run 'gcloud auth login' if needed)"
	@cd backupmanager && go run cli/main.go backup -env $(ENV) -run-id $(RUN)
	@echo "Backup completed successfully!"

restore: check-env-file
ifndef ENV
	$(error Error: ENV variable is not set. Usage: make restore ENV=<environment> FROM-RUN=<run-identifier> TARGETENV=<target-environment>)
endif
ifndef FROM-RUN
	$(error Error: FROM-RUN variable is not set. Usage: make restore ENV=<environment> FROM-RUN=<run-identifier> TARGETENV=<target-environment>)
endif
ifndef TARGETENV
	$(error Error: TARGETENV variable is not set. Usage: make restore ENV=<environment> FROM-RUN=<run-identifier> TARGETENV=<target-environment>)
endif
ifeq ($(ENV),staging)
else ifeq ($(ENV),production)
else
	$(error Error: ENV variable must be either 'staging' or 'production')
endif
ifeq ($(TARGETENV),staging)
else ifeq ($(TARGETENV),production)
else
	$(error Error: TARGETENV variable must be either 'staging' or 'production')
endif
	@echo "Restoring backup from $(ENV) environment (run ID: $(FROM-RUN)) to $(TARGETENV) environment..."
	@echo "This assumes you are authenticated with GCP (run 'gcloud auth login' if needed)"
	@cd backupmanager && go run cli/main.go restore -env $(ENV) -run-id $(FROM-RUN) -dest-env $(TARGETENV)
	@echo "Restore completed successfully!"

list-backups: check-env-file
ifndef ENV
	$(error Error: ENV variable is not set. Usage: make list-backups ENV=<environment>)
endif
ifeq ($(ENV),staging)
else ifeq ($(ENV),production)
else
	$(error Error: ENV variable must be either 'staging' or 'production')
endif
	@echo "Listing available backups for $(ENV) environment..."
	@echo "This assumes you are authenticated with GCP (run 'gcloud auth login' if needed)"
	@. backupmanager/cli/.env && gsutil ls -lh gs://$$BACKUP_BUCKET/backups/$(ENV)/ | grep -E "backup_.*\.tar\.gz$$" || echo "No backups found for $(ENV)"