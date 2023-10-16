import * as React from "react";
const SvgSgSingapore = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SG_-_Singapore_svg__a"
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
    <g mask="url(#SG_-_Singapore_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="SG_-_Singapore_svg__b"
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
      <g
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#SG_-_Singapore_svg__b)"
      >
        <path fill="#E31D1C" d="M0 0v6h16V0H0Z" />
        <path
          fill="#F1F9FF"
          d="M4.434 5.295s-1.413-.568-1.413-2.108 1.413-2.09 1.413-2.09c-.686-.173-2.509-.018-2.509 2.09 0 2.108 1.795 2.505 2.51 2.108Zm.362-.255.405-.244.415.244-.102-.477.337-.377h-.456l-.193-.446-.194.446-.457.02.338.357-.093.477Zm1.879-.272-.406.245.093-.477-.338-.358.457-.02.194-.445.193.446h.456l-.336.377.1.477-.413-.245ZM5.644 2.885l.405-.245.414.245-.101-.478.337-.376h-.457l-.193-.446-.193.446-.457.02.337.356-.092.478Zm-.96.726-.404.245.092-.477-.337-.358.457-.02.193-.445.193.446h.457l-.337.377.101.477-.414-.245Zm2.275.223.405-.245.414.245-.101-.477.337-.377h-.457l-.193-.446-.193.446-.457.02.337.357-.092.477Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgSgSingapore;
