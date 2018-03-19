<?php

namespace App\Http\Controllers\Admin\Customers;

use Laracommerce\Core\Customers\Customer;
use Laracommerce\Core\Customers\Repositories\CustomerRepository;
use Laracommerce\Core\Customers\Repositories\Interfaces\CustomerRepositoryInterface;
use Laracommerce\Core\Customers\Requests\CreateCustomerRequest;
use Laracommerce\Core\Customers\Requests\UpdateCustomerRequest;
use Laracommerce\Core\Customers\Transformations\CustomerTransformable;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    use CustomerTransformable;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepo;

    /**
     * CustomerController constructor.
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepo = $customerRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $list = $this->customerRepo->listCustomers('created_at', 'desc');

        if (request()->has('q')) {
            $list = $this->customerRepo->searchCustomer(request()->input('q'));
        }

        $customers = $list->map(function (Customer $customer) {
            return $this->transformCustomer($customer);
        })->all();


        return view('admin.customers.list', [
            'customers' => $this->customerRepo->paginateArrayResults($customers)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreateCustomerRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateCustomerRequest $request)
    {
        $this->customerRepo->createCustomer($request->except('_token', '_method'));

        return redirect()->route('admin.customers.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $customer = $this->customerRepo->findCustomerById($id);
        
        return view('admin.customers.show', [
            'customer' => $customer,
            'addresses' => $customer->addresses
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return view('admin.customers.edit', ['customer' => $this->customerRepo->findCustomerById($id)]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateCustomerRequest $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        $employee = $this->customerRepo->findCustomerById($id);

        $update = new CustomerRepository($employee);
        $data = $request->except('_method', '_token', 'password');

        if ($request->has('password')) {
            $data['password'] = bcrypt($request->input('password'));
        }

        $update->updateCustomer($data);

        $request->session()->flash('message', 'Update successful');
        return redirect()->route('admin.customers.edit', $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->customerRepo->delete($id);

        request()->session()->flash('message', 'Delete successful');
        return redirect()->route('admin.customers.index');
    }
}
