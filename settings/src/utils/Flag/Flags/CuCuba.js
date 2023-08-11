import * as React from "react";
const SvgCuCuba = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CU_-_Cuba_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#CU_-_Cuba_svg__a)">
      <path
        fill="#3D58DB"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="CU_-_Cuba_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#CU_-_Cuba_svg__b)">
        <path
          fill="#3D58DB"
          stroke="#F7FCFF"
          strokeWidth={2}
          d="M0 4h-1v4h18V4H0Z"
        />
      </g>
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12l9-6-9-6Z"
        clipRule="evenodd"
      />
      <mask
        id="CU_-_Cuba_svg__c"
        width={9}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12l9-6-9-6Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#CU_-_Cuba_svg__c)">
        <path
          fill="#F7FCFF"
          fillRule="evenodd"
          d="m3.344 7.108-1.836.97.894-1.948-1.14-1.04 1.407-.052.675-1.76.515 1.76h1.404L4.327 6.13l.744 1.947-1.727-.969Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgCuCuba;
