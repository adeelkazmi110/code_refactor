<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        // 1) declare $response if not fullfill both condition
        // 2)  Correction require so Removed if($user_id = $request->get('user_id')) 
        //     replace with  if($request->has('user_id'))
        $response = array();
        if($request->has('user_id')) {
            // directly get from request instead of variable 
            // $response = $this->repository->getUsersJobs($user_id);
            $response = $this->repository->getUsersJobs($request->get('user_id'));
        // indent the code 
        } else if($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')) {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        // get all except token and submit button
        // $data = $request->all();
        $data = $request->except(['_token', 'submit']);

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
       // $data = $request->all();
       //  $cuser = $request->__authenticatedUser;
       // get request with excet params and assigned values to function 
        $data = $request->except(['_token', 'submit']);
        $response = $this->repository->updateJob($id, $data, $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        // if($user_id = $request->get('user_id'))
        // Removed error and manage accordingly
        if($request->has('user_id')) {

            $response = $this->repository->getUsersJobsHistory($request->get('user_id'), $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        // getting id and pass it to function data was little bit confusing
        // $data = $request->get('job_id');
        $job_id = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($job_id, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        //$data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        // move this block into top if $data['admincomment'] is empty then return imediat instead of other extra calculation
        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }
        // at the end of this function queries based on job id so moved all the code into block because job id is necesary

        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];       

            if (isset($data['distance']) && $data['distance'] != "") {
                $distance = $data['distance'];
            } else {
                $distance = "";
            }
            if (isset($data['time']) && $data['time'] != "") {
                $time = $data['time'];
            } else {
                $time = "";
            }
            if (isset($data['session_time']) && $data['session_time'] != "") {
                $session = $data['session_time'];
            } else {
                $session = "";
            }
            if ($data['manually_handled'] == 'true') {
                $manually_handled = 'yes';
            } else {
                $manually_handled = 'no';
            }

            if ($data['by_admin'] == 'true') {
                $by_admin = 'yes';
            } else {
                $by_admin = 'no';
            }

            if (isset($data['admincomment']) && $data['admincomment'] != "") {
                $admincomment = $data['admincomment'];
            } else {
                $admincomment = "";
            }
            if ($time || $distance) {

                $affectedRows = Distance::where('job_id', $jobid)->update(array('distance' => $distance, 'time' => $time));
            }

            if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

                $affectedRows1 = Job::where('id', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));

            }

            return response('Record updated!');
        } else {
            return response('No Job Found!');
            // added if no job id found
        }
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        //$data = $request->all();
        // directly access job id through request  no need for above line
        $job = $this->repository->find($request->get('jobid'));
       // beow line is extra
        // $job_data = $this->repository->jobToData($job); 

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
            return response(['success' => $e->getMessage()]);
            // instead of success that should be error to return proper response
        }
    }

}
