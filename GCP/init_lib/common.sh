#!/usr/bin/env bash

props()
{
  # Read with:
  # IFS (Field Separator) =
  # -d (Record separator) newline
  # first field before separator as k (key)
  # second field after separator and reminder of record as v (value)
  while IFS='=' read -d $'\n' -r k v; do
    # Skip lines starting with sharp
    # or lines containing only space or empty lines
    [[ "$k" =~ ^([[:space:]]*|[[:space:]]*#.*)$ ]] && continue
    # Store key value into assoc array
    KEY+=($k)
    PROPERTIES[$k]="$v"
    # stdin the properties file
  done < "$1" ;
}







REDQUEST="rq"
REDCROSSQUEST="rcq"

declare -A CLOUD_FUNCTIONS=(["notifyRQOfRegistApproval"]="pubsub;queteur_approval_topic"  \
                                     ["ULQueteurStatsPerYear"]="pubsub;ul_update"         \
                                     ["ULTriggerRecompute"]="pubsub;trigger_ul_update"    \
                                     ["ztestCrossProjectFirestoCx"]="http;"               \
                                     ["findQueteurById"]="http;"                  \
                                     ["findULDetailsByToken"]="http;"             \
                                     ["historiqueTroncQueteur"]="http;"           \
                                     ["registerQueteur"]="http;"                  \
                                     ["troncListPrepared"]="http;"               \
                                     ["troncSetDepartOrRetour"]="http;"          \
                                     ["ztestCrossProjectSQLCx"]="http;")

declare -A FUNCTIONS_PROJECT_PREFIX=(["notifyRQOfRegistApproval"]="${REDCROSSQUEST}"    \
                                     ["ULQueteurStatsPerYear"]="${REDCROSSQUEST}"       \
                                     ["ULTriggerRecompute"]="${REDCROSSQUEST}"          \
                                     ["ztestCrossProjectFirestoCx"]="${REDCROSSQUEST}"  \
                                     ["findQueteurById"]="${REDQUEST}"                  \
                                     ["findULDetailsByToken"]="${REDQUEST}"             \
                                     ["historiqueTroncQueteur"]="${REDQUEST}"           \
                                     ["registerQueteur"]="${REDQUEST}"                  \
                                     ["troncListPrepared"]="${REDQUEST}"               \
                                     ["troncSetDepartOrRetour"]="${REDQUEST}"          \
                                     ["ztestCrossProjectSQLCx"]="${REDQUEST}")

#grant roles in RCQ projects to RQ & RCQ Cloud functions
declare -A FUNCTIONS_ROLES_RQ=(["notifyRQOfRegistApproval"]="roles/datastore.user;"       \
                               ["ULQueteurStatsPerYear"]="roles/datastore.user;"          \
                               ["ULTriggerRecompute"]=""                                  \
                               ["ztestCrossProjectFirestoCx"]="roles/datastore.viewer;"   \
                               ["findQueteurById"]="roles/datastore.viewer;"              \
                               ["findULDetailsByToken"]=""                                \
                               ["historiqueTroncQueteur"]="roles/datastore.user;"         \
                               ["registerQueteur"]=""                                     \
                               ["troncListPrepared"]="roles/datastore.viewer;"           \
                               ["troncSetDepartOrRetour"]="roles/datastore.viewer;"      \
                               ["ztestCrossProjectSQLCx"]="")

declare -A FUNCTIONS_ROLES_RCQ=(["notifyRQOfRegistApproval"]=""               \
                                 ["ULQueteurStatsPerYear"]="roles/cloudsql.client;roles/secretmanager.secretAccessor"  \
                                 ["ULTriggerRecompute"]="roles/cloudsql.client;roles/secretmanager.secretAccessor;roles/pubsub.publisher"                            \
                                 ["ztestCrossProjectFirestoCx"]="roles/datastore.viewer;"       \
                                 ["findQueteurById"]="roles/cloudsql.client;roles/secretmanager.secretAccessor"                               \
                                 ["findULDetailsByToken"]="roles/cloudsql.client;roles/secretmanager.secretAccessor;roles/datastore.viewer"   \
                                 ["historiqueTroncQueteur"]="roles/cloudsql.client;roles/secretmanager.secretAccessor"                        \
                                 ["registerQueteur"]="roles/cloudsql.client;roles/secretmanager.secretAccessor"                               \
                                 ["troncListPrepared"]="roles/cloudsql.client;roles/secretmanager.secretAccessor"                            \
                                 ["troncSetDepartOrRetour"]="roles/cloudsql.client;roles/secretmanager.secretAccessor"                       \
                                 ["ztestCrossProjectSQLCx"]="roles/cloudsql.client;roles/secretmanager.secretAccessor"             )


declare -A FUNCTIONS_EXTRA_PARAMS=(["ULTriggerRecompute"]="--timeout 540s")



#create one service account per function
#add roles to services accounts
#depending on the current project (rcq/rq), it will create the SA, and set the approrpiate rights
#most function  run in one project (ex: RCQ) and requires rights on the other project (RQ: firestore)
init_cloud_functions_create_service_accounts()
{

  #echo "Creating cloudfunctions service accounts"
  for FUNC_NAME in "${!CLOUD_FUNCTIONS[@]}"
  do
    RUNTIME_PROJECT=${FUNCTIONS_PROJECT_PREFIX[$FUNC_NAME]}

    if  [[ "${RUNTIME_PROJECT}1" == "${PROJECT_NAME}1" ]]
    then
      #echo "Creating cf-${FUNC_NAME} service account running in project ${RUNTIME_PROJECT}"
      echo "gcloud iam service-accounts create cf-${FUNC_NAME,,} "
      echo "  --description=\"Service Account for the cloud function '${FUNC_NAME}'\" "
      echo "  --display-name=\"Service Account for the cloud function '${FUNC_NAME}'\" "
                                             #lowerCase otherwise : ERROR: (gcloud.iam.service-accounts.create) argument NAME: Bad value [cf-ULTriggerRecompute]: Service account name must be between 6 and 30 characters (inclusive), must begin with a lowercase letter, and consist of lowercase alphanumeric characters that can be separated by hyphens.
      gcloud iam service-accounts create "cf-${FUNC_NAME,,}" \
        --description="Service Account for the cloud function '${FUNC_NAME}'" \
        --display-name="Service Account for the cloud function '${FUNC_NAME}'"

      #read -n 1 -s

    fi
  done
}


init_cloud_functions_grant_roles()
{

  echo "Creating cloudfunctions service accounts"
  for FUNC_NAME in "${!CLOUD_FUNCTIONS[@]}"
  do
    #get a ; separated value list of roles
    if  [[ "${PROJECT_NAME}1" == "rcq1" ]]
    then
      CLOUD_FUNCTIONS_ROLES=("${FUNCTIONS_ROLES_RCQ[$FUNC_NAME]//;/ }")
    else
      CLOUD_FUNCTIONS_ROLES=("${FUNCTIONS_ROLES_RQ[$FUNC_NAME]//;/ }")
    fi


    # shellcheck disable=SC2068
    for FUNC_ROLE in ${CLOUD_FUNCTIONS_ROLES[@]}
    do
      if  [[ "${FUNC_ROLE}1" != "1" ]]
      then
        RUNTIME_PROJECT=${FUNCTIONS_PROJECT_PREFIX[$FUNC_NAME]}
        GCLOUD_CMD="gcloud projects add-iam-policy-binding ${PROJECT_ID} --member serviceAccount:cf-${FUNC_NAME,,}@${RUNTIME_PROJECT}-${COUNTRY}-${ENV}.iam.gserviceaccount.com --role ${FUNC_ROLE}"
        echo "${GCLOUD_CMD}"
        ${GCLOUD_CMD}

        #read -n 1 -s
      fi
    done
  done
}
